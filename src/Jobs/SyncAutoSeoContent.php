<?php

namespace BlogCms\Jobs;

use BlogCms\Services\AutoSeo\AutoSeoException;
use BlogCms\Services\AutoSeo\AutoSeoSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued AutoSEO sync — pulls categories, authors, and posts (with their
 * related content + media) from the AutoSEO platform into the CMS. Meant for
 * scheduling in the host app (e.g. a nightly `$schedule->job(...)`), so the
 * potentially long-running media downloads never block a web request.
 *
 * Runs once ($tries = 1): the sync is idempotent, so a failed run is retried on
 * the next schedule tick rather than immediately re-downloading everything.
 */
class SyncAutoSeoContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Single attempt — the sync is idempotent, re-run on the next schedule. */
    public int $tries = 1;

    /** Generous timeout: a full sync can download many cover images/avatars. */
    public int $timeout = 1800;

    /**
     * @param  array  $only  Limit to specific resources (categories, authors, posts); empty = all.
     */
    public function __construct(
        public array $only = [],
        public bool $force = false,
    ) {}

    public function handle(AutoSeoSyncService $service): void
    {
        try {
            $service->sync(
                $this->only,
                false,
                $this->force,
                fn (string $message) => Log::info('[autoseo-sync] '.$message),
            );
        } catch (AutoSeoException $e) {
            Log::error('[autoseo-sync] '.$e->getMessage());
            $this->fail($e);
        }
    }
}
