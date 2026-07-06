<?php

namespace BlogCms\Models;

use BlogCms\Models\Concerns\HasCanonicalPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class BlogPost extends Model implements HasMedia
{
    use HasCanonicalPage, HasSlug, InteractsWithMedia, SoftDeletes;

    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;
    const STATUS_ARCHIVED = 2;

    const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    protected $fillable = [
        'blog_category_id',
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'locale',
        'is_featured',
        'views_count',
        'published_at',
        'meta_title',
        'meta_description',
        'howto_title',
        'howto_description',
        'faq_title',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopeReadable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'user_id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(BlogPostFaq::class)->orderBy('sort_order');
    }

    public function howToSteps(): HasMany
    {
        return $this->hasMany(BlogPostHowToStep::class)->orderBy('sort_order');
    }

    public function citations(): HasMany
    {
        return $this->hasMany(BlogPostCitation::class)->orderBy('sort_order');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(BlogPostTopic::class)->orderBy('sort_order');
    }

    public function basedOn(): HasMany
    {
        return $this->hasMany(BlogPostBasedOn::class)->orderBy('sort_order');
    }

    public function hooks(): HasMany
    {
        return $this->hasMany(BlogPostHook::class)->orderBy('position')->orderBy('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_desktop')
            ->singleFile();

        $this->addMediaCollection('cover_mobile')
            ->singleFile();

        $this->addMediaCollection('gallery');
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
