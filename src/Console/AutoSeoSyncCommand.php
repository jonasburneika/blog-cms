<?php

namespace BlogCms\Console;

use BlogCms\Services\AutoSeo\AutoSeoClient;
use BlogCms\Services\AutoSeo\AutoSeoException;
use BlogCms\Services\AutoSeo\AutoSeoSyncService;
use Illuminate\Console\Command;

/**
 * Pull categories, authors, and posts (with their related content and media)
 * from the AutoSEO platform into the CMS, idempotently via the AutoSeoMapping
 * table. Use --ping to only verify connectivity/credentials, --dry-run to
 * preview changes without writing, and --force to re-import even when the
 * upstream payload is unchanged.
 */
class AutoSeoSyncCommand extends Command
{
    protected $signature = 'blog-cms:autoseo-sync
        {--only=* : Limit to specific resources (categories, authors, posts)}
        {--dry-run : Report what would change without writing}
        {--force : Re-import even when payloads are unchanged}
        {--ping : Only test connectivity/credentials and exit}';

    protected $description = 'Sync blog content (categories, authors, posts, media) from the AutoSEO platform';

    /** Resources the sync service knows how to import. */
    private const RESOURCES = ['categories', 'authors', 'posts'];

    public function handle(AutoSeoClient $client, AutoSeoSyncService $service): int
    {
        try {
            if (! $client->isConfigured()) {
                $this->error('AutoSEO is not configured. Set AUTOSEO_BASE_URL, AUTOSEO_API_TOKEN and AUTOSEO_SITE_ID (or the matching settings-table overrides).');

                return self::FAILURE;
            }

            if ($this->option('ping')) {
                return $this->ping($client);
            }

            $only = $this->resolveOnly();
            if ($only === null) {
                return self::FAILURE;
            }

            $dryRun = (bool) $this->option('dry-run');
            $force = (bool) $this->option('force');

            if ($dryRun) {
                $this->comment('Dry run — no changes will be written.');
            }

            $stats = $service->sync(
                $only,
                $dryRun,
                $force,
                fn (string $message) => $this->line('  '.$message),
            );

            $this->renderStats($stats);

            return self::SUCCESS;
        } catch (AutoSeoException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /** Verify connectivity + credentials and print the resolved site. */
    private function ping(AutoSeoClient $client): int
    {
        $client->ping();
        $site = $client->site();

        $this->info('AutoSEO connection OK.');
        $this->line('  Site:   '.($site['name'] ?? '—'));
        $this->line('  Status: '.($site['status'] ?? '—'));

        return self::SUCCESS;
    }

    /**
     * Validate + normalise the --only values. Returns the requested subset (or
     * the empty array = "all"), or null when an unknown value was supplied.
     */
    private function resolveOnly(): ?array
    {
        $only = array_values(array_unique((array) $this->option('only')));

        $unknown = array_diff($only, self::RESOURCES);
        if ($unknown !== []) {
            $this->error('Unknown --only value(s): '.implode(', ', $unknown).'. Allowed: '.implode(', ', self::RESOURCES).'.');

            return null;
        }

        return $only;
    }

    /** Render the per-resource sync stats as a table. */
    private function renderStats(array $stats): void
    {
        $rows = [];
        foreach ($stats as $resource => $counts) {
            $rows[] = [
                $resource,
                $counts['created'] ?? 0,
                $counts['updated'] ?? 0,
                $counts['skipped'] ?? 0,
                $counts['failed'] ?? 0,
            ];
        }

        if ($rows === []) {
            $this->info('Nothing to sync.');

            return;
        }

        $this->newLine();
        $this->table(['Resource', 'Created', 'Updated', 'Skipped', 'Failed'], $rows);
    }
}
