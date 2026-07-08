<?php

namespace BlogCms\Support;

use BlogCms\Models\BlogAuthorProfile;
use BlogCms\Models\BlogPost;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Spatie\SchemaOrg\Article;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\HowTo;
use Spatie\SchemaOrg\Person;
use Spatie\SchemaOrg\Schema;

/**
 * Builds Spatie\SchemaOrg JSON-LD graphs for a blog post.
 *
 * Ported from the app's resources/views/frontend/post.blade.php @push('schema')
 * block. Kept as plain builder methods (rather than baking the logic into the
 * Blade components) so apps can also call these directly if they need the
 * schema objects for something other than an inline <script> tag.
 */
class SchemaBuilder
{
    public static function article(BlogPost $post): Article
    {
        $articleSchema = Schema::article()
            ->headline($post->title)
            ->description($post->meta_description ?: $post->excerpt)
            ->datePublished($post->published_at?->toIso8601String())
            ->dateModified($post->updated_at->toIso8601String())
            ->author(static::personFor(static::authorProfile($post), static::authorName($post)));

        // Images as ImageObjects with captions (cover + captioned content photos).
        $images = static::imageObjects($post);
        if ($images) {
            $articleSchema->image(count($images) === 1 ? $images[0] : $images);
        }

        // Editorial reviewer → reviewedBy (an established expert who vetted it).
        if ($reviewer = static::reviewerProfile($post)) {
            $articleSchema->reviewedBy(static::personFor($reviewer, $reviewer->name()));
        }

        if ($post->citations->count()) {
            $articleSchema->citation($post->citations->map(fn ($c) => Schema::webPage()
                ->setProperty('@type', $c->type ?: 'WebPage')
                ->setProperty('@id', $c->url)
                ->name($c->name)
            )->toArray());
        }

        if ($post->topics->count()) {
            $articleSchema->about($post->topics->map(function ($topic) {
                $thing = Schema::thing()
                    ->setProperty('@type', $topic->type ?: 'Thing')
                    ->name($topic->name);

                if ($topic->same_as) {
                    $thing->sameAs($topic->same_as);
                }

                return $thing;
            })->toArray());
        }

        if ($post->basedOn->count()) {
            $articleSchema->isBasedOn($post->basedOn->pluck('url')->toArray());
        }

        // Like count as schema.org UserLikes (interactionStatistic / InteractionCounter).
        $articleSchema->interactionStatistic(
            Schema::interactionCounter()
                ->interactionType('https://schema.org/UserLikes')
                ->userInteractionCount((int) ($post->likes_count ?? 0))
        );

        return $articleSchema;
    }

    public static function faqPage(BlogPost $post): ?FAQPage
    {
        if (! $post->faqs->count()) {
            return null;
        }

        $faqItems = $post->faqs->map(fn ($faq) => Schema::question()
            ->name($faq->question)
            ->acceptedAnswer(Schema::answer()->text($faq->answer))
        )->toArray();

        return Schema::fAQPage()->mainEntity($faqItems);
    }

    public static function howTo(BlogPost $post): ?HowTo
    {
        if (! $post->howToSteps->count()) {
            return null;
        }

        $howToSteps = $post->howToSteps->map(fn ($step) => Schema::howToStep()
            ->name($step->title)
            ->text($step->content)
        )->toArray();

        return Schema::howTo()
            ->name($post->howto_title ?: $post->title)
            ->description($post->howto_description ?: $post->excerpt)
            ->step($howToSteps);
    }

    /**
     * Author name for the Article's Person node.
     *
     * Source hardcoded a brand-specific fallback string; core falls back to
     * the app's own name via config('app.name') instead, so it stays neutral
     * across any theme that consumes this package.
     */
    protected static function authorName(BlogPost $post): string
    {
        return $post->author?->name ?: config('app.name');
    }

    /**
     * A Person node for an author/reviewer profile, enriched for E-E-A-T: job
     * title, the 150-word bio as `description`, avatar, the internal author-page
     * `url` (the definitive identity anchor), `sameAs` verification links,
     * `knowsAbout` expertise, and `hasCredential` degrees/certs. Falls back to a
     * bare Person with just the name when no profile exists.
     */
    protected static function personFor(?BlogAuthorProfile $profile, string $fallbackName): Person
    {
        $person = Schema::person()->name($profile?->name() ?: $fallbackName);

        if (! $profile) {
            return $person;
        }

        if ($profile->role) {
            $person->jobTitle($profile->role);
        }
        if ($bio = trim((string) $profile->bio)) {
            $person->description($bio);
        }
        if ($avatar = $profile->avatarUrl()) {
            $person->image($avatar);
        }
        if ($profile->is_indexable) {
            $person->url($profile->pageUrl());
        }
        if ($sameAs = array_column($profile->socialLinks(), 'url')) {
            $person->sameAs($sameAs);
        }
        if ($knowsAbout = $profile->knowsAbout()) {
            $person->knowsAbout($knowsAbout);
        }
        if ($credentials = static::credentialSchemas($profile)) {
            $person->hasCredential(count($credentials) === 1 ? $credentials[0] : $credentials);
        }

        return $person;
    }

    /** EducationalOccupationalCredential nodes for a profile's credentials. */
    protected static function credentialSchemas(BlogAuthorProfile $profile): array
    {
        return array_map(function (array $c) {
            $credential = Schema::educationalOccupationalCredential()->name($c['name']);
            if (! empty($c['category'])) {
                $credential->credentialCategory($c['category']);
            }

            return $credential;
        }, $profile->credentials());
    }

    protected static function authorProfile(BlogPost $post): ?BlogAuthorProfile
    {
        return $post->relationLoaded('authorProfile')
            ? $post->authorProfile
            : $post->authorProfile()->first();
    }

    protected static function reviewerProfile(BlogPost $post): ?BlogAuthorProfile
    {
        if (! $post->reviewed_by) {
            return null;
        }

        return $post->relationLoaded('reviewerProfile')
            ? $post->reviewerProfile
            : $post->reviewerProfile()->first();
    }

    /**
     * Article images as ImageObjects with captions: the cover, plus original
     * content photos that carry alt text (their caption). Google rewards
     * first-hand, captioned imagery — the alt/caption should echo the article's
     * core keywords.
     *
     * @return array<int, \Spatie\SchemaOrg\ImageObject>
     */
    protected static function imageObjects(BlogPost $post): array
    {
        $objects = [];

        if ($cover = static::coverUrl($post)) {
            $image = Schema::imageObject()->url($cover)->contentUrl($cover);
            if ($caption = ($post->meta_title ?: $post->title)) {
                $image->caption($caption);
            }
            $objects[] = $image;
        }

        foreach (static::contentImages($post) as $img) {
            $objects[] = Schema::imageObject()
                ->url($img['src'])
                ->contentUrl($img['src'])
                ->caption($img['caption']);
        }

        return $objects;
    }

    /**
     * Captioned images from the post body (current locale): only `<img>` that
     * carry alt text, since that alt is the caption we expose. Bounded so a
     * gallery-heavy post doesn't produce an enormous graph.
     *
     * @return array<int, array{src:string,caption:string}>
     */
    protected static function contentImages(BlogPost $post, int $limit = 12): array
    {
        $html = (string) $post->content;
        if ($html === '' || ! str_contains($html, '<img')) {
            return [];
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $images = [];
        foreach ((new DOMXPath($doc))->query('//img') as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }
            $src = trim($img->getAttribute('src'));
            $alt = trim($img->getAttribute('alt'));
            if ($src === '' || $alt === '') {
                continue; // only original, described photos become ImageObjects
            }
            $images[] = ['src' => $src, 'caption' => $alt];
            if (count($images) >= $limit) {
                break;
            }
        }

        return $images;
    }

    /**
     * Cover image URL for the Article's image field.
     *
     * Source used $post->getFirstMediaUrl('cover_desktop', 'preview') directly
     * in the Blade view (not a theme helper) — InteractsWithMedia is native to
     * BlogCms\Models\BlogPost, so this works unchanged in core. Falls back to
     * a plain 'featured_image' column in case a post has no media library
     * upload but does have a legacy/manual image URL set.
     */
    public static function coverUrl(BlogPost $post): ?string
    {
        return $post->getFirstMediaUrl('cover_desktop', 'preview')
            ?: $post->getFirstMediaUrl('cover_desktop')
            ?: $post->featured_image
            ?: null;
    }
}
