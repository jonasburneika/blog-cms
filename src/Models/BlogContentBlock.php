<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A reusable rich-text block the theme renders by key, e.g.
 * `<x-blog::content-block key="homepage_before_featured" />`. Title + content
 * are translatable HTML authored in the admin WYSIWYG.
 */
class BlogContentBlock extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'content'];

    /** Suggested keys for the homepage before/after the featured posts. */
    public const HOMEPAGE_BEFORE = 'homepage_before_featured';
    public const HOMEPAGE_AFTER = 'homepage_after_featured';

    protected $fillable = ['key', 'name', 'title', 'content', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function forKey(string $key): ?self
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    public function title(?string $locale = null): string
    {
        return (string) $this->getTranslation('title', $locale ?: app()->getLocale(), true);
    }

    /** Rendered HTML for the current (or given) locale. */
    public function html(?string $locale = null): string
    {
        return (string) $this->getTranslation('content', $locale ?: app()->getLocale(), true);
    }
}
