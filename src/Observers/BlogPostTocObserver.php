<?php

namespace BlogCms\Observers;

use BlogCms\Services\PostTocGenerator;
use BlogCms\Support\LocaleManager;
use Illuminate\Database\Eloquent\Model;

/**
 * Regenerates each post's table of contents when its content changes.
 *
 * Runs on `saving` (before persistence, so it needs no second write) and works
 * per locale: a TOC is generated for every enabled language whose content hash
 * changed, and stale languages are dropped. Disabling the TOC clears the data.
 */
class BlogPostTocObserver
{
    public function __construct(private PostTocGenerator $generator) {}

    public function saving(Model $post): void
    {
        if (! $post->toc_enabled) {
            $post->toc_items = null;
            $post->content_rendered = null;
            $post->toc_hash = null;
            $post->toc_generated_at = null;

            return;
        }

        $items = $post->toc_items ?? [];
        $rendered = $post->content_rendered ?? [];
        $hashes = $post->toc_hash ?? [];
        $changed = false;

        foreach (LocaleManager::all() as $locale) {
            $content = (string) $post->getTranslation('content', $locale, false);

            // No real content for this locale → drop any stale generated data.
            if (trim(strip_tags($content)) === '') {
                if (isset($items[$locale]) || isset($rendered[$locale]) || isset($hashes[$locale])) {
                    unset($items[$locale], $rendered[$locale], $hashes[$locale]);
                    $changed = true;
                }
                continue;
            }

            $hash = hash('sha256', $content);
            if (($hashes[$locale] ?? null) === $hash && array_key_exists($locale, $items)) {
                continue; // unchanged since last generation
            }

            $result = $this->generator->generate($content);
            $items[$locale] = $result->items;
            $rendered[$locale] = $result->html;
            $hashes[$locale] = $hash;
            $changed = true;
        }

        if ($changed) {
            $post->toc_items = $items ?: null;
            $post->content_rendered = $rendered ?: null;
            $post->toc_hash = $hashes ?: null;
            $post->toc_generated_at = now();
        }
    }
}
