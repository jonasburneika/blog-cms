<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class BlogCategory extends Model implements HasMedia
{
    use HasSlug, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'menu_title',
        'slug',
        'description',
        'meta_title',
        'status',
        'sort_order',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function scopeHasReadablePosts(Builder $query): Builder
    {
        return $query->whereHas('posts', fn (Builder $q) => $q->readable());
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
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
