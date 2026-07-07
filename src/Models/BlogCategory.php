<?php

namespace BlogCms\Models;

use BlogCms\Models\Concerns\HasCanonicalPage;
use BlogCms\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class BlogCategory extends Model implements HasMedia
{
    use HasCanonicalPage, HasTranslatableSlug, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** Fields stored per-locale as JSON via spatie/laravel-translatable. */
    public array $translatable = [
        'title',
        'menu_title',
        'slug',
        'description',
        'meta_title',
    ];

    protected $fillable = [
        'parent_id',
        'title',
        'menu_title',
        'slug',
        'description',
        'meta_title',
        'status',
        'sort_order',
    ];

    public function scopeHasReadablePosts(Builder $query): Builder
    {
        return $query->whereHas('posts', fn (Builder $q) => $q->readable());
    }

    /** Only top-level categories (no parent). */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /** The parent category, or null when this is a root (top-level) category. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Direct child categories. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** True when this category has no parent (sits at the top level). */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * IDs of this category plus every descendant. Used to keep the parent
     * picker acyclic — a category may never be nested under itself or one of
     * its own descendants.
     */
    public function subtreeIds(): array
    {
        $ids = [$this->getKey()];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->subtreeIds());
        }

        return $ids;
    }

    /**
     * Categories eligible to be this one's parent: all categories except the
     * category itself and its descendants (which would create a cycle). Pass a
     * null/new category to get every category.
     */
    public static function parentOptionsFor(?self $category = null): Collection
    {
        $exclude = $category && $category->exists ? $category->subtreeIds() : [];

        return static::query()
            ->when($exclude, fn (Builder $q) => $q->whereNotIn('id', $exclude))
            ->orderBy('sort_order')
            ->get();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_desktop')
            ->singleFile();

        $this->addMediaCollection('cover_mobile')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600);
    }
}
