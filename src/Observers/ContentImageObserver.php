<?php

namespace BlogCms\Observers;

use BlogCms\Services\ContentImageIngestor;
use BlogCms\Support\LocaleManager;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * After a post is saved, pull any inline content images into MediaLibrary and
 * rewrite the HTML to the stored URLs — for every locale. Runs on `saved` (the
 * model has an id, so media can attach), then refreshes the table of contents
 * from the final HTML and persists via saveQuietly (no event re-entry).
 *
 * Covers all save paths (token API, admin, programmatic) in one place.
 */
class ContentImageObserver
{
    public function __construct(private ContentImageIngestor $ingestor) {}

    public function saved(Model $post): void
    {
        if (! config('blog-cms.media.ingest_content_images', true) || ! $post instanceof HasMedia) {
            return;
        }

        $collection = config('blog-cms.media.content_collection', 'content');
        $changed = false;

        foreach (LocaleManager::all() as $locale) {
            $content = (string) $post->getTranslation('content', $locale, false);
            if ($content === '' || ! str_contains($content, '<img')) {
                continue;
            }

            $rewritten = $this->ingestor->ingest($post, $content, $collection);
            if ($rewritten !== $content) {
                $post->setTranslation('content', $locale, $rewritten);
                $changed = true;
            }
        }

        if (! $changed) {
            return;
        }

        // Rebuild the TOC + rendered HTML from the final (rewritten) content,
        // then persist without firing events again (avoids re-ingest loops).
        app(BlogPostTocObserver::class)->saving($post);
        $post->saveQuietly();
    }
}
