<?php

namespace BlogCms\Services\AutoSeo\Importers;

use BlogCms\Models\AutoSeoMapping;
use BlogCms\Models\BlogCategory;
use BlogCms\Services\AutoSeo\AutoSeoClient;
use BlogCms\Support\LocaleManager;
use Illuminate\Support\Facades\DB;

/**
 * Imports AutoSEO blog categories into local BlogCategory models.
 *
 * Categories form a flat tree (parent_id references another category). They are
 * processed parent-first so a child's parent is already mapped when it lands.
 * Each row is content-hashed; an unchanged hash is skipped unless $force.
 */
class CategoryImporter
{
    public function __construct(protected AutoSeoClient $client) {}

    /**
     * @param  callable  $log  fn(string $message): void
     * @return array{created:int,updated:int,skipped:int,failed:int}
     */
    public function import(bool $dryRun, bool $force, callable $log): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        $rows = $this->client->categories();
        $rows = $this->sortParentFirst($rows);

        $default = LocaleManager::default();

        foreach ($rows as $row) {
            $remoteId = (int) ($row['id'] ?? 0);
            if ($remoteId <= 0) {
                $stats['failed']++;

                continue;
            }

            $hash = sha1((string) json_encode($row));
            $mapping = AutoSeoMapping::forRemote(AutoSeoMapping::TYPE_CATEGORY, $remoteId);
            $categoryClass = $this->categoryClass();
            /** @var BlogCategory|null $existing */
            $existing = $mapping ? $categoryClass::find($mapping->local_id) : null;

            // Skip only when the local category still exists — a locally
            // deleted one must be re-imported even when upstream is unchanged.
            if ($mapping && $existing && $mapping->payload_hash === $hash && ! $force) {
                $stats['skipped']++;
                $log("Category #{$remoteId} \"".($row['name'] ?? '')."\" unchanged — skipped");

                continue;
            }

            $isUpdate = $existing !== null;

            if ($dryRun) {
                $stats[$isUpdate ? 'updated' : 'created']++;
                $log(($isUpdate ? 'Would update' : 'Would create')." category #{$remoteId} \"".($row['name'] ?? '')."\"");

                continue;
            }

            try {
                DB::transaction(function () use ($row, $remoteId, $hash, $default, $existing, $categoryClass, $log, &$stats) {
                    $category = $existing ?? new $categoryClass;

                    $created = ! $category->exists;

                    $parentLocalId = null;
                    if (! empty($row['parent_id'])) {
                        $parentLocalId = AutoSeoMapping::localId(AutoSeoMapping::TYPE_CATEGORY, (int) $row['parent_id']);
                        if ($parentLocalId === null) {
                            $log("Category #{$remoteId}: parent #{$row['parent_id']} not yet mapped — importing as root");
                        }
                    }

                    LocaleManager::withLocale($default, function () use ($category, $row, $parentLocalId, $default) {
                        $category->parent_id = $parentLocalId;
                        $category->title = (string) ($row['name'] ?? '');
                        $category->menu_title = (string) ($row['name'] ?? '');

                        // Keep the remote slug but de-collide it; when absent,
                        // HasTranslatableSlug derives one from the title.
                        $slug = trim((string) ($row['slug'] ?? ''));
                        if ($slug !== '') {
                            $category->slug = $category->uniqueSlugFor($slug, $default);
                        }

                        $category->description = (string) ($row['long_description'] ?? $row['description'] ?? '');
                        $category->meta_title = $row['meta_title'] ?? null;
                        $category->sort_order = (int) ($row['sort_order'] ?? 0);
                        $category->status = ($row['is_active'] ?? true) ? 'active' : 'inactive';
                        $category->save();
                    });

                    AutoSeoMapping::record(AutoSeoMapping::TYPE_CATEGORY, $remoteId, $category, $hash);

                    $stats[$created ? 'created' : 'updated']++;
                    $log(($created ? 'Created' : 'Updated')." category #{$remoteId} → local #{$category->id} \"".($row['name'] ?? '')."\"");
                });
            } catch (\Throwable $e) {
                $stats['failed']++;
                $log("Category #{$remoteId} failed: ".$e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Order rows so every parent precedes its children (within the batch),
     * using the rows' own parent_id links. Cycle-safe via a visited guard.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array<string,mixed>>
     */
    protected function sortParentFirst(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            if (isset($row['id'])) {
                $byId[(int) $row['id']] = $row;
            }
        }

        $ordered = [];
        $visited = [];

        $visit = function (array $row) use (&$visit, &$ordered, &$visited, $byId) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || isset($visited[$id])) {
                return;
            }
            $visited[$id] = true;

            $parentId = (int) ($row['parent_id'] ?? 0);
            if ($parentId > 0 && isset($byId[$parentId])) {
                $visit($byId[$parentId]);
            }

            $ordered[] = $row;
        };

        foreach ($rows as $row) {
            $visit($row);
        }

        return $ordered;
    }

    /**
     * The (host-app-swappable) category model class.
     *
     * @return class-string<BlogCategory>
     */
    protected function categoryClass(): string
    {
        return config('blog-cms.models.category', BlogCategory::class);
    }
}
