<?php

namespace BlogCms\Models;

use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * One historical URL of a post/category, pointing at the entity that now lives
 * elsewhere. Written by UrlHistoryRecorder on slug changes; read by
 * HandleRedirects when a request path no longer resolves to a live page.
 */
class BlogUrlHistory extends Model
{
    protected $table = 'blog_url_history';

    protected $fillable = [
        'old_path',
        'target_type',
        'target_id',
        'locale',
        'status_code',
    ];

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'status_code' => 'integer',
        ];
    }

    /** Current absolute URL of the target, resolved live (so chains collapse). */
    public function targetUrl(): ?string
    {
        return PageUrl::url($this->target_type, $this->target_id, $this->locale);
    }

    /** Drop history rows pointing at a deleted entity. */
    public static function purgeTarget(string $type, int|string $id): void
    {
        static::query()
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->delete();
    }
}
