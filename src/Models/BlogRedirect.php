<?php

namespace BlogCms\Models;

use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * A redirect from one live blog page to another (category <-> post, any combo).
 *
 * The source is always an existing post/category; visiting its URL 301/302s to
 * the target's *current* URL (resolved live, so the redirect follows the target
 * if the target later moves). `source_path` is a cached lookup key kept fresh by
 * the observers registered in BlogCmsServiceProvider.
 */
class BlogRedirect extends Model
{
    public const STATUS_PERMANENT = 301;
    public const STATUS_TEMPORARY = 302;

    public const STATUSES = [
        self::STATUS_PERMANENT => 'Permanent (301)',
        self::STATUS_TEMPORARY => 'Temporary (302)',
    ];

    protected $fillable = [
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'status_code',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'target_id' => 'integer',
            'status_code' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep the cached lookup path in sync with the source entity on every write.
        static::saving(function (self $redirect) {
            $redirect->source_path = PageUrl::path($redirect->source_type, $redirect->source_id);
        });
    }

    // ─── Resolution ──────────────────────────────────────────────

    public function sourceModel(): ?Model
    {
        return PageUrl::model($this->source_type, $this->source_id);
    }

    public function targetModel(): ?Model
    {
        return PageUrl::model($this->target_type, $this->target_id);
    }

    /** Absolute URL of the target page, resolved live. Null if unresolvable. */
    public function targetUrl(): ?string
    {
        return PageUrl::url($this->target_type, $this->target_id);
    }

    public function sourceLabel(): string
    {
        return static::labelFor($this->source_type, $this->sourceModel());
    }

    public function targetLabel(): string
    {
        return static::labelFor($this->target_type, $this->targetModel());
    }

    protected static function labelFor(string $type, ?Model $model): string
    {
        if (! $model) {
            return '(deleted)';
        }

        return ucfirst($type).': '.($model->title ?? $model->getKey());
    }

    // ─── Maintenance (called by observers) ───────────────────────

    /**
     * Recompute source_path for every redirect whose source page moved because
     * this entity was saved. A category slug change also moves its posts, so
     * those redirects are refreshed too.
     */
    public static function refreshForModel(string $type, int $id): void
    {
        $postIds = [];

        if ($type === PageUrl::TYPE_CATEGORY) {
            $postClass = config('blog-cms.models.post');
            $postIds = $postClass::where('blog_category_id', $id)->pluck('id')->all();
        }

        static::query()
            ->where(fn ($q) => $q->where('source_type', $type)->where('source_id', $id))
            ->when($postIds, fn ($q) => $q->orWhere(
                fn ($sub) => $sub->where('source_type', PageUrl::TYPE_POST)->whereIn('source_id', $postIds)
            ))
            ->get()
            ->each
            ->save();
    }

    /** Remove redirects that reference a now-deleted entity (as source or target). */
    public static function purgeReferences(string $type, int $id): void
    {
        static::query()
            ->where(fn ($q) => $q->where('source_type', $type)->where('source_id', $id))
            ->orWhere(fn ($q) => $q->where('target_type', $type)->where('target_id', $id))
            ->delete();
    }
}
