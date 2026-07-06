<?php

namespace BlogCms\Models;

use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * A redirect from one live blog page to another (category <-> post, any combo).
 *
 * The source is always an existing post/category; visiting its URL 301/302s to
 * the target's *current* URL (resolved live, so the redirect follows the target
 * if it later moves). With per-locale slugs the middleware resolves the request
 * path dynamically (strip locale prefix → localized slug → entity), so there is
 * no cached path to keep in sync.
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

    // ─── Resolution ──────────────────────────────────────────────

    public function sourceModel(): ?Model
    {
        return PageUrl::model($this->source_type, $this->source_id);
    }

    public function targetModel(): ?Model
    {
        return PageUrl::model($this->target_type, $this->target_id);
    }

    /** Absolute URL of the target page in the given locale, resolved live. */
    public function targetUrl(?string $locale = null): ?string
    {
        return PageUrl::url($this->target_type, $this->target_id, $locale);
    }

    /** Look up the redirect owned by a given source entity, if any. */
    public static function forSource(string $type, int|string $id): ?self
    {
        return static::query()
            ->where('source_type', $type)
            ->where('source_id', $id)
            ->first();
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

        $title = $model->getTranslation('title', \BlogCms\Support\LocaleManager::default(), true);

        return ucfirst($type).': '.($title ?: $model->getKey());
    }

    // ─── Maintenance (called by observers) ───────────────────────

    /** Remove redirects that reference a now-deleted entity (source or target). */
    public static function purgeReferences(string $type, int|string $id): void
    {
        static::query()
            ->where(fn ($q) => $q->where('source_type', $type)->where('source_id', $id))
            ->orWhere(fn ($q) => $q->where('target_type', $type)->where('target_id', $id))
            ->delete();
    }
}
