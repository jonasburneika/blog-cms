<?php

namespace BlogCms\Models;

use BlogCms\Models\Concerns\HasTranslatableSlug;
use BlogCms\Support\LocaleManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A simple standalone content page (Terms, Privacy, Cookie Policy, …). The app
 * owns the public route and renders the (trusted, admin-authored) HTML; this
 * model provides the data, a slug per locale, and a URL helper.
 */
class BlogPage extends Model
{
    use HasTranslatableSlug, HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'slug', 'content', 'meta_title', 'meta_description'];

    protected $fillable = [
        'title', 'slug', 'content', 'meta_title', 'meta_description',
        'status', 'is_indexable', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_indexable' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Find a published page by its slug in the given (or current) locale. */
    public static function findPublished(string $slug, ?string $locale = null): ?self
    {
        $locale = $locale ?: LocaleManager::current();

        return static::published()->where("slug->{$locale}", $slug)->first();
    }

    /** Path (no leading slash) for the page; app owns the actual route. */
    public function pagePath(?string $locale = null): string
    {
        $locale = $locale ?: LocaleManager::current();
        $slug = (string) $this->getTranslation('slug', $locale, true);
        $prefix = \BlogCms\Support\PageUrl::pagePrefix($locale);

        return trim(($prefix !== '' ? $prefix.'/' : '').$slug, '/');
    }

    public function pageUrl(?string $locale = null): string
    {
        return url($this->pagePath($locale));
    }
}
