<?php

namespace BlogCms\Jobs;

use BlogCms\Services\ContentImageIngestor;
use BlogCms\Services\ShortPixelOptimizer;
use BlogCms\Support\ImageFormats;
use BlogCms\Support\LocaleManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\HasMedia;

/**
 * Optimize (and, when enabled, convert to WebP/AVIF) all of a post's content
 * images via ShortPixel. One job per post so the content HTML is re-woven
 * exactly once — after the format variants exist — with no cross-job races.
 *
 * A pristine copy of each original is kept before the first optimization
 * (config media.shortpixel.keep_backup) so we can always re-optimize from the
 * source and fall back if an optimization goes wrong. Downloaded bytes are
 * validated as real images before anything is overwritten, and only kept when
 * actually smaller.
 *
 * With $reoptimize = true the post's images are first reset (originals restored
 * from backup, existing variants + flags cleared) and then run through the
 * current ShortPixel settings again. ShortPixel is async — a "pending" result
 * re-queues the whole job (already-done images are skipped) up to a bounded
 * number of attempts. Every failure is logged and swallowed.
 */
class OptimizePostImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $postId,
        public int $pollAttempt = 1,
        public bool $reoptimize = false,
    ) {}

    public function handle(ShortPixelOptimizer $optimizer, ContentImageIngestor $ingestor): void
    {
        if (! $optimizer->enabled()) {
            return;
        }

        $postClass = config('blog-cms.models.post');
        $post = $postClass::find($this->postId);
        if (! $post instanceof HasMedia) {
            return;
        }

        $collection = config('blog-cms.media.content_collection', 'content');

        // Reoptimize: restore pristine originals + drop existing variants/flags
        // once, on the first pass, so the loop below re-runs everything fresh.
        if ($this->reoptimize && $this->pollAttempt === 1) {
            $this->resetForReoptimize($post, $collection);
        }

        $pending = false;
        $processed = false;

        foreach ($post->getMedia($collection) as $media) {
            if ($media->getCustomProperty('shortpixel_optimized')) {
                continue;
            }

            $item = $optimizer->reduce($media->getFullUrl());
            if ($item === null) {
                continue; // transport error already logged; retry on the next save
            }

            $code = (int) data_get($item, 'Status.Code', -1);

            if ($code === ShortPixelOptimizer::STATUS_PENDING) {
                $pending = true;

                continue;
            }

            if ($code !== ShortPixelOptimizer::STATUS_SUCCESS) {
                Log::warning('[blog-cms] ShortPixel returned an error', [
                    'media' => $media->getKey(),
                    'code' => $code,
                    'message' => data_get($item, 'Status.Message'),
                ]);

                continue;
            }

            $this->applyResult($post, $media, $item, $optimizer, $collection);
            $processed = true;
        }

        if ($pending) {
            $this->repollOrGiveUp();

            return;
        }

        if ($processed) {
            $this->reweaveContent($post, $ingestor, $collection);
        }
    }

    /** Write the optimized original back and store the converted format variants. */
    protected function applyResult(Model $post, mixed $media, array $item, ShortPixelOptimizer $optimizer, string $collection): void
    {
        try {
            // Keep a pristine copy before we ever overwrite the original.
            $this->backupOriginal($media);

            $originalSize = (int) $media->size;

            // Optimized original bytes → overwrite in place (URL unchanged), only
            // when it's a valid image AND ShortPixel actually made it smaller.
            $url = $optimizer->optimizedUrl($item);
            $bytes = $url ? $optimizer->download($url) : null;
            if ($this->isImage($bytes) && ($originalSize <= 0 || strlen($bytes) < $originalSize)) {
                Storage::disk($media->disk)->put($media->getPathRelativeToRoot(), $bytes);
                $media->size = strlen($bytes);
                $originalSize = strlen($bytes);
            }

            // ShortPixel-converted WebP/AVIF → store as sibling variant media,
            // but only when a valid image smaller than the original (else a
            // <picture> would serve a *bigger* modern format).
            foreach ($optimizer->formats() as $format) {
                if ($media->getCustomProperty("variant_{$format}_id")) {
                    continue; // already stored
                }

                $formatUrl = $optimizer->formatUrl($item, $format);
                $formatBytes = $formatUrl ? $optimizer->download($formatUrl) : null;
                if (! $this->isImage($formatBytes)) {
                    continue;
                }
                if ($originalSize > 0 && strlen($formatBytes) >= $originalSize) {
                    continue; // not smaller — skip, keep only the original
                }

                $base = pathinfo((string) $media->file_name, PATHINFO_FILENAME);
                $variant = $post->addMediaFromString($formatBytes)
                    ->usingFileName($base.'.'.$format)
                    ->withCustomProperties(['variant_of' => $media->getKey(), 'variant_format' => $format])
                    ->toMediaCollection($collection.'-'.$format);

                $media->setCustomProperty("variant_{$format}_id", $variant->getKey());
            }

            $media->setCustomProperty('shortpixel_optimized', true);
            $media->setCustomProperty('shortpixel_savings', data_get($item, 'PercentImprovement'));
            $media->save();

            // Rebuild the original's srcset buckets from the optimized bytes.
            app(FileManipulator::class)->createDerivedFiles($media);
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] ShortPixel apply failed', [
                'media' => $media->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Copy the pristine original aside once, so we can restore/re-optimize from it. */
    protected function backupOriginal(mixed $media): void
    {
        if (! config('blog-cms.media.shortpixel.keep_backup', true)) {
            return;
        }
        if ($media->getCustomProperty('original_backup')) {
            return; // already backed up
        }

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();
        $backup = $this->backupPath($path);

        if ($disk->exists($path)) {
            $disk->copy($path, $backup);
            $media->setCustomProperty('original_backup', $backup);
            $media->setCustomProperty('original_size', (int) $media->size);
            $media->save();
        }
    }

    protected function backupPath(string $path): string
    {
        return dirname($path).'/shortpixel-backup/'.basename($path);
    }

    /**
     * Prepare a post for reoptimization: restore each content image from its
     * backup, delete the WebP/AVIF variant media, and clear the optimize flags
     * so the main loop reprocesses everything with the current settings.
     */
    protected function resetForReoptimize(HasMedia $post, string $collection): void
    {
        // Drop every converted variant (linked or orphaned) in one go.
        foreach (ImageFormats::SUPPORTED as $format) {
            $post->clearMediaCollection($collection.'-'.$format);
        }

        foreach ($post->getMedia($collection) as $media) {
            $this->restoreOriginal($media);

            foreach (ImageFormats::SUPPORTED as $format) {
                $media->forgetCustomProperty("variant_{$format}_id");
            }
            $media->forgetCustomProperty('shortpixel_optimized');
            $media->forgetCustomProperty('shortpixel_savings');
            $media->save();

            // Regenerate the srcset buckets from the restored original.
            app(FileManipulator::class)->createDerivedFiles($media);
        }
    }

    /** Restore the pristine original from its backup (no-op if none was kept). */
    protected function restoreOriginal(mixed $media): void
    {
        $backup = $media->getCustomProperty('original_backup');
        if (! $backup) {
            return;
        }

        $disk = Storage::disk($media->disk);
        if ($disk->exists($backup)) {
            $disk->copy($backup, $media->getPathRelativeToRoot());
            if ($size = $media->getCustomProperty('original_size')) {
                $media->size = (int) $size;
            }
            $media->save();
        }
    }

    /** True when the bytes are a decodable image (guards against corrupt output). */
    protected function isImage(?string $bytes): bool
    {
        return $bytes !== null && $bytes !== '' && @getimagesizefromstring($bytes) !== false;
    }

    /** Re-run the ingestor so each content <img> is wrapped in a <picture>. */
    protected function reweaveContent(Model $post, ContentImageIngestor $ingestor, string $collection): void
    {
        $changed = false;

        foreach (LocaleManager::all() as $locale) {
            $content = (string) $post->getTranslation('content', $locale, false);
            if ($content === '' || ! str_contains($content, '<img')) {
                continue;
            }

            $rewritten = $ingestor->ingest($post, $content, $collection);
            if ($rewritten !== $content) {
                $post->setTranslation('content', $locale, $rewritten);
                $changed = true;
            }
        }

        if ($changed) {
            $post->saveQuietly(); // no event re-entry
        }
    }

    /** Re-queue the whole job to check pending images again, until attempts run out. */
    protected function repollOrGiveUp(): void
    {
        $max = (int) config('blog-cms.media.shortpixel.max_poll_attempts', 6);
        if ($this->pollAttempt >= $max) {
            Log::warning('[blog-cms] ShortPixel still pending after max attempts', ['post' => $this->postId]);

            return;
        }

        $delay = (int) config('blog-cms.media.shortpixel.poll_delay', 20);

        static::dispatch($this->postId, $this->pollAttempt + 1, $this->reoptimize)
            ->delay(now()->addSeconds($delay))
            ->onQueue(config('blog-cms.media.shortpixel.queue'));
    }
}
