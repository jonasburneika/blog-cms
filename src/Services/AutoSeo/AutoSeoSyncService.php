<?php

namespace BlogCms\Services\AutoSeo;

use BlogCms\Services\AutoSeo\Importers\AuthorImporter;
use BlogCms\Services\AutoSeo\Importers\CategoryImporter;
use BlogCms\Services\AutoSeo\Importers\PostImporter;

/**
 * Orchestrates a full AutoSEO → blog-cms import.
 *
 * Runs the importers in dependency order (categories → authors → posts) so
 * posts can resolve their category and author mappings. Each importer returns
 * per-entity stats; the aggregate is returned keyed by entity.
 */
class AutoSeoSyncService
{
    public function __construct(protected AutoSeoClient $client) {}

    /**
     * @param  list<string>  $only  subset of ['categories','authors','posts']; empty = all
     * @param  callable|null  $log  fn(string $message): void
     * @return array<string,array{created:int,updated:int,skipped:int,failed:int}>
     */
    public function sync(array $only = [], bool $dryRun = false, bool $force = false, ?callable $log = null): array
    {
        if (! $this->client->isConfigured()) {
            throw new AutoSeoException('AutoSEO client is not configured (missing base URL or API key).');
        }

        $log ??= static fn (string $message) => null;

        $runs = $only === [] ? ['categories', 'authors', 'posts'] : $only;
        $results = [];

        if (in_array('categories', $runs, true)) {
            $log('Importing categories…');
            $results['categories'] = (new CategoryImporter($this->client))->import($dryRun, $force, $log);
        }

        if (in_array('authors', $runs, true)) {
            $log('Importing authors…');
            $results['authors'] = (new AuthorImporter($this->client))->import($dryRun, $force, $log);
        }

        if (in_array('posts', $runs, true)) {
            $log('Importing posts…');
            $results['posts'] = (new PostImporter($this->client))->import($dryRun, $force, $log);
        }

        return $results;
    }
}
