<?php

namespace BlogCms\Observers;

use BlogCms\Jobs\OptimizePostImages;
use BlogCms\Services\ContentImageIngestor;
use BlogCms\Services\ShortPixelOptimizer;
use BlogCms\Support\LocaleManager;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * After a post is saved, pull any inline content images into MediaLibrary and
 * rewrite the HTML to the stored URLs, and lazy-load every content image — for
 * every locale. Runs on `saved` (the model has an id, so media can attach), then
 * refreshes the table of contents from the final HTML and persists via
 * saveQuietly (no event re-entry).
 *
 * The lazy-loading pass runs even when image ingestion is disabled
 * (config media.ingest_content_images) — that toggle only affects the
 * remote/base64 fetch, handled inside the ingestor.
 *
 * Covers all save paths (token API, admin, programmatic) in one place.
 */
class ContentImageObserver
{
    public function __construct(
        private ContentImageIngestor $ingestor,
        private ShortPixelOptimizer $shortpixel,
    ) {}

    public function saved(Model $post): void
    {
        if (! $post instanceof HasMedia) {
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

        if ($changed) {
            // Rebuild the TOC + rendered HTML from the final (rewritten) content,
            // then persist without firing events again (avoids re-ingest loops).
            app(BlogPostTocObserver::class)->saving($post);
            $post->saveQuietly();
        }

        $this->queueImageOptimization($post, $collection);
    }

    /**
     * Queue a single ShortPixel optimization job for the post when it has any
     * content image not yet optimized. The per-image `shortpixel_optimized` flag
     * keeps this idempotent across saves.
     */
    protected function queueImageOptimization(HasMedia $post, string $collection): void
    {
        if (! $this->shortpixel->enabled()) {
            return;
        }

        $hasPending = $post->getMedia($collection)
            ->contains(fn ($media) => ! $media->getCustomProperty('shortpixel_optimized'));

        if ($hasPending) {
            OptimizePostImages::dispatch($post->getKey())
                ->onQueue(config('blog-cms.media.shortpixel.queue'));
        }
    }
}
