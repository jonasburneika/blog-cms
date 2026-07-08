<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named list of links (e.g. "footer"), fetched by the theme via its key:
 * `<x-blog::link-list key="footer" />` or `BlogLinkList::forKey('footer')`.
 */
class BlogLinkList extends Model
{
    protected $fillable = ['key', 'name', 'sort_order'];

    public function links(): HasMany
    {
        return $this->hasMany(BlogLink::class)->orderBy('sort_order');
    }

    /** Active links only, ordered — what a theme renders. */
    public function activeLinks(): HasMany
    {
        return $this->hasMany(BlogLink::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public static function forKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }
}
