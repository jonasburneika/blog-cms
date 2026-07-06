<?php

namespace BlogCms\Services;

use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogUrlHistory;
use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * Records old URLs when a published post/category slug changes, so the previous
 * links 301 to the current URL. Called from the `saved` model events, where
 * getOriginal() still holds the pre-save slug JSON.
 */
class UrlHistoryRecorder
{
    /** A post's slug changed → record its old URL per locale. */
    public function recordPostChange(Model $post): void
    {
        if (! $post->wasChanged('slug') || ! $this->isPublished($post)) {
            return;
        }

        $oldSlugs = $this->decodeOriginalSlug($post);
        $category = $post->category;
        if (! $category) {
            return;
        }

        foreach (LocaleManager::all() as $locale) {
            $old = $oldSlugs[$locale] ?? null;
            $new = $post->getTranslation('slug', $locale, false);
            if (! $old || $old === $new) {
                continue;
            }

            $categorySlug = $category->getTranslation('slug', $locale, true);
            if (! $categorySlug) {
                continue;
            }

            $this->store(
                $this->join(LocaleManager::prefix($locale), $categorySlug, $old),
                PageUrl::TYPE_POST,
                $post->getKey(),
                $locale,
            );
        }
    }

    /**
     * A category's slug changed → record the old category URL *and* the old URL
     * of every published post beneath it (their path embeds the category slug).
     */
    public function recordCategoryChange(Model $category): void
    {
        if (! $category->wasChanged('slug')) {
            return;
        }

        $oldSlugs = $this->decodeOriginalSlug($category);
        $posts = $category->posts()->get();

        foreach (LocaleManager::all() as $locale) {
            $old = $oldSlugs[$locale] ?? null;
            $new = $category->getTranslation('slug', $locale, false);
            if (! $old || $old === $new) {
                continue;
            }

            $prefix = LocaleManager::prefix($locale);

            $this->store($this->join($prefix, $old), PageUrl::TYPE_CATEGORY, $category->getKey(), $locale);

            foreach ($posts as $post) {
                if (! $this->isPublished($post)) {
                    continue;
                }
                $postSlug = $post->getTranslation('slug', $locale, false);
                if ($postSlug) {
                    $this->store($this->join($prefix, $old, $postSlug), PageUrl::TYPE_POST, $post->getKey(), $locale);
                }
            }
        }
    }

    /** Persist one old_path → target, guarding against self-loops and live shadows. */
    protected function store(string $oldPath, string $type, int|string $id, string $locale): void
    {
        if ($oldPath === '') {
            return;
        }

        $currentPath = PageUrl::path($type, $id, $locale);

        // The old path is (again) the live path — never shadow a live page.
        if ($oldPath === $currentPath) {
            BlogUrlHistory::where('old_path', $oldPath)->delete();

            return;
        }

        BlogUrlHistory::updateOrCreate(
            ['old_path' => $oldPath],
            ['target_type' => $type, 'target_id' => $id, 'locale' => $locale, 'status_code' => 301],
        );

        // If the target just moved onto a path that used to be historical, that
        // path is live again — drop the stale history row so it isn't redirected.
        if ($currentPath !== null) {
            BlogUrlHistory::where('old_path', $currentPath)->delete();
        }
    }

    /** @return array<string, string> locale => old slug */
    protected function decodeOriginalSlug(Model $model): array
    {
        $original = $model->getOriginal('slug');

        if (is_array($original)) {
            return $original;
        }

        $decoded = json_decode((string) $original, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function isPublished(Model $post): bool
    {
        return (int) $post->status === BlogPost::STATUS_PUBLISHED;
    }

    protected function join(string ...$parts): string
    {
        return implode('/', array_filter($parts, fn ($p) => $p !== '' && $p !== null));
    }
}
