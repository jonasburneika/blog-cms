<?php

namespace BlogCms\Jobs;

use BlogCms\Services\ShortPixelOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Optimize a single content image via ShortPixel, in place. Downloads the
 * optimized (same-format) bytes, overwrites the stored file, marks the media so
 * it isn't re-optimized, and regenerates the responsive srcset conversions.
 *
 * ShortPixel processes asynchronously: a "pending" result re-dispatches the job
 * with a delay, up to a bounded number of attempts. Every failure is logged and
 * swallowed — optimization must never disrupt content.
 */
class OptimizeMediaWithShortPixel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $mediaId,
        public int $pollAttempt = 1,
    ) {}

    public function handle(ShortPixelOptimizer $optimizer): void
    {
        if (! $optimizer->enabled()) {
            return;
        }

        /** @var Media|null $media */
        $media = Media::find($this->mediaId);
        if (! $media || $media->getCustomProperty('shortpixel_optimized')) {
            return; // gone, or already optimized (idempotent)
        }

        $item = $optimizer->reduce($media->getFullUrl());
        if ($item === null) {
            return; // transport error already logged
        }

        $code = (int) data_get($item, 'Status.Code', -1);

        if ($code === ShortPixelOptimizer::STATUS_PENDING) {
            $this->repollOrGiveUp();

            return;
        }

        if ($code !== ShortPixelOptimizer::STATUS_SUCCESS) {
            Log::warning('[blog-cms] ShortPixel returned an error', [
                'media' => $this->mediaId,
                'code' => $code,
                'message' => data_get($item, 'Status.Message'),
            ]);

            return;
        }

        $url = $optimizer->optimizedUrl($item);
        $bytes = $url ? $optimizer->download($url) : null;
        if ($bytes === null || $bytes === '') {
            return;
        }

        $this->writeBack($media, $bytes, $item);
    }

    /** Overwrite the stored file with optimized bytes and refresh conversions. */
    protected function writeBack(Media $media, string $bytes, array $item): void
    {
        try {
            Storage::disk($media->disk)->put($media->getPathRelativeToRoot(), $bytes);

            $media->size = strlen($bytes);
            $media->setCustomProperty('shortpixel_optimized', true);
            $media->setCustomProperty('shortpixel_savings', data_get($item, 'PercentImprovement'));
            $media->save();

            // Rebuild the srcset buckets from the now-optimized original.
            app(FileManipulator::class)->createDerivedFiles($media);
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] ShortPixel write-back failed', [
                'media' => $this->mediaId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Re-queue the job to check again later, until the attempt budget runs out. */
    protected function repollOrGiveUp(): void
    {
        $max = (int) config('blog-cms.media.shortpixel.max_poll_attempts', 6);
        if ($this->pollAttempt >= $max) {
            Log::warning('[blog-cms] ShortPixel still pending after max attempts', ['media' => $this->mediaId]);

            return;
        }

        $delay = (int) config('blog-cms.media.shortpixel.poll_delay', 20);

        static::dispatch($this->mediaId, $this->pollAttempt + 1)
            ->delay(now()->addSeconds($delay))
            ->onQueue(config('blog-cms.media.shortpixel.queue'));
    }
}
