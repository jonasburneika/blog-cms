<?php

namespace BlogCms\Services\AutoSeo\Importers;

use BlogCms\Models\AutoSeoMapping;
use BlogCms\Models\BlogAuthorProfile;
use BlogCms\Models\BlogPost;
use BlogCms\Services\AutoSeo\AutoSeoClient;
use BlogCms\Services\AutoSeo\RemoteMediaDownloader;
use BlogCms\Support\LocaleManager;
use Illuminate\Support\Facades\DB;

/**
 * Imports AutoSEO blog posts (with all locale variants, schema children and
 * cover imagery) into local BlogPost models.
 *
 * Posts that are translations of one another share a locale_group_id; every
 * variant in a group maps to a single BlogPost whose translatable fields carry
 * each locale. The group is content-hashed (volatile metrics excluded) so an
 * unchanged group is skipped unless $force.
 */
class PostImporter
{
    protected RemoteMediaDownloader $media;

    public function __construct(protected AutoSeoClient $client)
    {
        $this->media = new RemoteMediaDownloader($client);
    }

    /**
     * @param  callable  $log  fn(string $message): void
     * @return array{created:int,updated:int,skipped:int,failed:int}
     */
    public function import(bool $dryRun, bool $force, callable $log): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        $this->allowInlineImagesFromAutoSeo();

        $default = LocaleManager::default();
        $enabledLocales = LocaleManager::all();

        // Fetch full detail for every list row (list rows omit schema_links etc.).
        $details = [];
        foreach ($this->client->posts() as $listRow) {
            $id = (int) ($listRow['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            try {
                $details[] = $this->client->post($id);
            } catch (\Throwable $e) {
                $stats['failed']++;
                $log("Post #{$id} detail fetch failed: ".$e->getMessage());
            }
        }

        foreach ($this->groupByLocaleGroup($details) as $variants) {
            // Deterministic order: default-locale primary, then by id.
            usort($variants, fn ($a, $b) => (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));
            $primary = $this->pickPrimary($variants, $default);
            $primaryId = (int) ($primary['id'] ?? 0);

            $groupHash = $this->groupHash($variants);
            $mapping = AutoSeoMapping::forRemote(AutoSeoMapping::TYPE_POST, $primaryId);
            $postClass = $this->postClass();
            /** @var BlogPost|null $existingPost */
            $existingPost = $mapping ? $postClass::find($mapping->local_id) : null;

            // Skip only when the local post still exists — a locally deleted
            // post must be re-imported even if the upstream payload is unchanged.
            if ($mapping && $existingPost && $mapping->payload_hash === $groupHash && ! $force) {
                $stats['skipped']++;
                $log("Post #{$primaryId} \"".($primary['title'] ?? '')."\" unchanged — skipped");

                continue;
            }

            // Category is required — it must have been mapped by CategoryImporter.
            $categoryLocalId = ! empty($primary['category_id'])
                ? AutoSeoMapping::localId(AutoSeoMapping::TYPE_CATEGORY, (int) $primary['category_id'])
                : null;

            $isUpdate = $existingPost !== null;

            if ($dryRun) {
                // A dry run writes no categories, so their mappings may not
                // exist yet — note it instead of reporting a failure.
                $note = $categoryLocalId === null
                    ? ' [category #'.($primary['category_id'] ?? 'null').' not mapped yet — will resolve after a real categories import]'
                    : '';

                $stats[$isUpdate ? 'updated' : 'created']++;
                $log(($isUpdate ? 'Would update' : 'Would create')." post #{$primaryId} \"".($primary['title'] ?? '')."\" (".count($variants).' locale(s))'.$note);

                continue;
            }

            if ($categoryLocalId === null) {
                $stats['failed']++;
                $log("Post #{$primaryId} \"".($primary['title'] ?? '')."\": category #".($primary['category_id'] ?? 'null')." not mapped — skipped");

                continue;
            }

            try {
                DB::transaction(function () use ($variants, $primary, $primaryId, $groupHash, $categoryLocalId, $default, $enabledLocales, $existingPost, $postClass, $log, $dryRun, &$stats) {
                    $post = $existingPost ?? new $postClass;

                    $created = ! $post->exists;

                    // Non-translatable attributes (set once, from the primary variant).
                    $post->blog_category_id = $categoryLocalId;
                    $post->user_id = $this->resolveAuthorUserId($primary['author_id'] ?? null)
                        ?? config('blog-cms.api.default_author_id');
                    $post->reviewed_by = $this->resolveReviewerUserId($primary['reviewed_by_author_id'] ?? null);
                    $post->status = $this->mapStatus($primary['status'] ?? null);
                    $post->published_at = $primary['published_at'] ?? $primary['publish_date'] ?? null;
                    $post->locale = $primary['locale'] ?? $default;

                    // Translatable fields, one locale per variant; drop
                    // translations for locales removed upstream.
                    $this->applyVariants($post, $variants, $enabledLocales, $log);
                    if (! $created) {
                        $this->forgetRemovedLocales($post, $variants, $default);
                    }

                    // HowTo title/description live on the post (primary locale).
                    $this->applyHowToHeader($post, $primary['howto_schema'] ?? null, $default);

                    $post->save();

                    // Children: delete-and-recreate from the primary variant.
                    $this->syncChildren($post, $primary);

                    // Cover imagery (best-effort; never aborts the import). A
                    // failed download leaves $coversOk false so the group hash
                    // is withheld and the next sync retries the covers.
                    $coversOk = true;
                    if (! empty($primary['desktop_cover_media_id'])) {
                        $coversOk = $this->media->attach($post, 'cover_desktop', (int) $primary['desktop_cover_media_id'], $dryRun) !== null && $coversOk;
                    }
                    if (! empty($primary['mobile_cover_media_id'])) {
                        $coversOk = $this->media->attach($post, 'cover_mobile', (int) $primary['mobile_cover_media_id'], $dryRun) !== null && $coversOk;
                    }

                    // Map every variant id → this post (hash only on the
                    // primary row), and drop mappings of variants that no
                    // longer exist upstream.
                    $variantIds = [];
                    foreach ($variants as $variant) {
                        $vid = (int) ($variant['id'] ?? 0);
                        if ($vid <= 0) {
                            continue;
                        }
                        $variantIds[] = $vid;
                        AutoSeoMapping::record(
                            AutoSeoMapping::TYPE_POST,
                            $vid,
                            $post,
                            $vid === $primaryId ? ($coversOk ? $groupHash : null) : null,
                        );
                    }

                    AutoSeoMapping::query()
                        ->where('remote_type', AutoSeoMapping::TYPE_POST)
                        ->where('local_type', get_class($post))
                        ->where('local_id', $post->getKey())
                        ->whereNotIn('remote_id', $variantIds)
                        ->delete();

                    $stats[$created ? 'created' : 'updated']++;
                    $log(($created ? 'Created' : 'Updated')." post #{$primaryId} → local #{$post->id} \"".($primary['title'] ?? '')."\"");
                });
            } catch (\Throwable $e) {
                $stats['failed']++;
                $log("Post #{$primaryId} \"".($primary['title'] ?? '')."\" failed: ".$e->getMessage());
            }
        }

        return $stats;
    }

    // ─── Grouping ────────────────────────────────────────────────

    /**
     * Group post details by locale_group_id. Rows with a null group each form
     * their own singleton group.
     *
     * @param  array<int,array<string,mixed>>  $details
     * @return array<int|string,array<int,array<string,mixed>>>
     */
    protected function groupByLocaleGroup(array $details): array
    {
        $groups = [];
        foreach ($details as $detail) {
            $group = $detail['locale_group_id'] ?? null;
            $key = ($group === null || $group === '') ? 'post_'.($detail['id'] ?? '') : 'grp_'.$group;
            $groups[$key][] = $detail;
        }

        return $groups;
    }

    /**
     * The primary variant: the one whose locale is the site default, else the
     * first.
     *
     * @param  array<int,array<string,mixed>>  $variants
     * @return array<string,mixed>
     */
    protected function pickPrimary(array $variants, string $default): array
    {
        foreach ($variants as $variant) {
            if (($variant['locale'] ?? null) === $default) {
                return $variant;
            }
        }

        return $variants[0];
    }

    /**
     * A stable hash of the whole group, excluding volatile fields that change
     * without the content meaningfully changing.
     *
     * @param  array<int,array<string,mixed>>  $variants
     */
    protected function groupHash(array $variants): string
    {
        $sanitized = array_map(function (array $variant) {
            unset($variant['word_count'], $variant['reading_time_min'], $variant['updated_at'], $variant['has_refresh_draft'], $variant['refresh_draft'], $variant['change_notes']);

            return $variant;
        }, $variants);

        usort($sanitized, fn ($a, $b) => (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));

        return sha1((string) json_encode($sanitized));
    }

    // ─── Field mapping ───────────────────────────────────────────

    /**
     * Write each variant's translatable fields under its own locale.
     *
     * @param  array<int,array<string,mixed>>  $variants
     * @param  array<int,string>  $enabledLocales
     */
    protected function applyVariants(BlogPost $post, array $variants, array $enabledLocales, callable $log): void
    {
        $previous = app()->getLocale();

        try {
            foreach ($variants as $variant) {
                $locale = (string) ($variant['locale'] ?? LocaleManager::default());

                if (! in_array($locale, $enabledLocales, true)) {
                    $log("Post #".($variant['id'] ?? '')." variant locale \"{$locale}\" is not enabled — imported anyway");
                }

                app()->setLocale($locale);

                $post->title = (string) ($variant['title'] ?? '');

                // Keep the remote slug (it matches AutoSEO-published URLs) but
                // de-collide it against existing local content; when absent,
                // leave it to HasTranslatableSlug to derive from the title.
                $slug = trim((string) ($variant['slug'] ?? ''));
                if ($slug !== '') {
                    $post->slug = $post->uniqueSlugFor($slug, $locale);
                }

                $post->excerpt = $variant['summary'] ?? null;
                $post->content = $variant['content'] ?? null;
                $post->meta_title = $variant['meta_title'] ?? null;
                $post->meta_description = $variant['meta_description'] ?? null;
            }
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * Remove translations whose locale variant disappeared upstream, mirroring
     * the delete-and-recreate handling of child records.
     *
     * @param  array<int,array<string,mixed>>  $variants
     */
    protected function forgetRemovedLocales(BlogPost $post, array $variants, string $default): void
    {
        $kept = array_map(
            static fn (array $variant) => (string) ($variant['locale'] ?? $default),
            $variants,
        );

        foreach (['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description'] as $field) {
            foreach (array_keys($post->getTranslations($field)) as $locale) {
                if (! in_array($locale, $kept, true)) {
                    $post->forgetTranslation($field, $locale);
                }
            }
        }
    }

    protected function applyHowToHeader(BlogPost $post, ?array $howto, string $default): void
    {
        if (! $howto) {
            return;
        }

        LocaleManager::withLocale($default, function () use ($post, $howto) {
            if (! empty($howto['name'])) {
                $post->howto_title = (string) $howto['name'];
            }
            if (! empty($howto['description'])) {
                $post->howto_description = (string) $howto['description'];
            }
        });
    }

    /**
     * The (host-app-swappable) post model class.
     *
     * @return class-string<BlogPost>
     */
    protected function postClass(): string
    {
        return config('blog-cms.models.post', BlogPost::class);
    }

    protected function mapStatus(?string $status): int
    {
        $map = (array) config('blog-cms.autoseo.status_map', ['draft' => 0, 'review' => 0, 'published' => 1]);

        return (int) ($map[$status] ?? 0);
    }

    protected function resolveAuthorUserId(?int $authorId): ?int
    {
        if (! $authorId) {
            return null;
        }

        $profileId = AutoSeoMapping::localId(AutoSeoMapping::TYPE_AUTHOR, (int) $authorId);
        if ($profileId === null) {
            return null;
        }

        return BlogAuthorProfile::find($profileId)?->user_id;
    }

    protected function resolveReviewerUserId(?int $authorId): ?int
    {
        return $this->resolveAuthorUserId($authorId);
    }

    // ─── Children ────────────────────────────────────────────────

    protected function syncChildren(BlogPost $post, array $primary): void
    {
        // Delete-and-recreate so a re-import reflects removals/reorders.
        $post->topics()->delete();
        $post->citations()->delete();
        $post->basedOn()->delete();
        $post->faqs()->delete();
        $post->howToSteps()->delete();

        // keywords[] → topics
        $sort = 0;
        foreach ((array) ($primary['keywords'] ?? []) as $keyword) {
            $keyword = trim((string) $keyword);
            if ($keyword === '') {
                continue;
            }
            $post->topics()->create(['name' => $keyword, 'sort_order' => $sort++]);
        }

        $schemaLinks = (array) ($primary['schema_links'] ?? []);

        // schema_links.citations → citations
        $sort = 0;
        foreach ((array) ($schemaLinks['citations'] ?? []) as $citation) {
            $url = trim((string) ($citation['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $post->citations()->create([
                'name' => (string) ($citation['name'] ?? $url),
                'url' => $url,
                'sort_order' => $sort++,
            ]);
        }

        // schema_links.is_based_on → based_on
        $sort = 0;
        foreach ((array) ($schemaLinks['is_based_on'] ?? []) as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }
            $post->basedOn()->create(['url' => $url, 'sort_order' => $sort++]);
        }

        // faq_schema → faqs
        $sort = 0;
        foreach ($this->parseFaq($primary['faq_schema'] ?? null) as $faq) {
            $post->faqs()->create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $sort++,
            ]);
        }

        // howto_schema steps → howToSteps
        $sort = 0;
        foreach ($this->parseHowToSteps($primary['howto_schema'] ?? null) as $step) {
            $post->howToSteps()->create([
                'title' => $step['title'],
                'content' => $step['content'],
                'image' => $step['image'],
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * Parse a FAQPage JSON-LD object into [{question, answer}] rows. Accepts a
     * top-level mainEntity, or FAQPage/Question nodes nested under @graph.
     * Questions are stripped of HTML; answers keep their HTML.
     *
     * @return array<int,array{question:string,answer:string}>
     */
    protected function parseFaq(?array $schema): array
    {
        if (! $schema) {
            return [];
        }

        $entities = [];

        $collect = function ($node) use (&$entities) {
            if (! is_array($node)) {
                return;
            }
            if (! empty($node['mainEntity'])) {
                foreach ((array) $node['mainEntity'] as $entity) {
                    $entities[] = $entity;
                }
            }
        };

        $collect($schema);
        foreach ((array) ($schema['@graph'] ?? []) as $node) {
            $collect($node);
            // A bare Question node directly in @graph.
            if (is_array($node) && isset($node['@type']) && $node['@type'] === 'Question') {
                $entities[] = $node;
            }
        }

        $faqs = [];
        foreach ($entities as $entity) {
            if (! is_array($entity)) {
                continue;
            }

            $question = trim(strip_tags((string) ($entity['name'] ?? $entity['question'] ?? '')));
            if ($question === '') {
                continue;
            }

            $answer = $entity['acceptedAnswer'] ?? $entity['answer'] ?? '';
            if (is_array($answer)) {
                $answer = $answer['text'] ?? '';
            }
            $answer = trim((string) $answer);

            $faqs[] = ['question' => $question, 'answer' => $answer];
        }

        return $faqs;
    }

    /**
     * Parse a HowTo JSON-LD object's steps into [{title, content, image}] rows.
     *
     * @return array<int,array{title:string,content:string,image:?string}>
     */
    protected function parseHowToSteps(?array $schema): array
    {
        if (! $schema) {
            return [];
        }

        $rawSteps = $schema['step'] ?? $schema['itemListElement'] ?? [];

        $steps = [];
        foreach ((array) $rawSteps as $step) {
            if (! is_array($step)) {
                continue;
            }

            $title = trim(strip_tags((string) ($step['name'] ?? '')));
            $content = trim((string) ($step['text'] ?? ''));
            if ($title === '' && $content === '') {
                continue;
            }

            $image = $step['image'] ?? null;
            if (is_array($image)) {
                $image = $image['url'] ?? null;
            }
            $image = ($image !== null && trim((string) $image) !== '') ? trim((string) $image) : null;

            $steps[] = ['title' => $title, 'content' => $content, 'image' => $image];
        }

        return $steps;
    }

    // ─── Inline images ───────────────────────────────────────────

    /**
     * Register the AutoSEO host as an allowed inline-image source so the content
     * image ingestor (which runs on post save) can fetch body images from it.
     */
    protected function allowInlineImagesFromAutoSeo(): void
    {
        $host = $this->client->baseHost();
        if (! $host) {
            return;
        }

        $hosts = (array) config('blog-cms.media.allowed_image_hosts', []);

        // An empty list means "any public host is allowed" (see ImageUrlGuard)
        // — adding ours would flip it into a restrictive allowlist and block
        // every other image host, so only extend an already-active allowlist.
        if ($hosts === [] || in_array($host, $hosts, true)) {
            return;
        }

        $hosts[] = $host;
        config(['blog-cms.media.allowed_image_hosts' => $hosts]);
    }
}
