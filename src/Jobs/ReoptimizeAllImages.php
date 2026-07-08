<?php

namespace BlogCms\Jobs;

use BlogCms\Services\ShortPixelOptimizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fan-out job behind the admin "Reoptimize all images" action: dispatches one
 * OptimizePostImages(reoptimize: true) per post that has content images, so
 * every image is restored from its backup and run through the CURRENT ShortPixel
 * settings again. Chunked so a large library doesn't build one huge batch.
 */
class ReoptimizeAllImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ShortPixelOptimizer $optimizer): void
    {
        if (! $optimizer->enabled()) {
            return;
        }

        $postClass = config('blog-cms.models.post');
        $collection = config('blog-cms.media.content_collection', 'content');
        $queue = config('blog-cms.media.shortpixel.queue');

        $postClass::query()->with('media')->chunkById(200, function ($posts) use ($collection, $queue) {
            foreach ($posts as $post) {
                if ($post->getMedia($collection)->isNotEmpty()) {
                    OptimizePostImages::dispatch($post->getKey(), 1, true)->onQueue($queue);
                }
            }
        });
    }
}
