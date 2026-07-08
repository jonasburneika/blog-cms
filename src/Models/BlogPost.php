<?php

namespace BlogCms\Models;

use BlogCms\Models\Concerns\HasCanonicalPage;
use BlogCms\Models\Concerns\HasTableOfContents;
use BlogCms\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class BlogPost extends Model implements HasMedia
{
    use HasCanonicalPage, HasTableOfContents, HasTranslatableSlug, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** Fields stored per-locale as JSON via spatie/laravel-translatable. */
    public array $translatable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'og_title',
        'twitter_title',
        'howto_title',
        'howto_description',
        'faq_title',
    ];

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
        'reviewed_by',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'locale',
        'is_featured',
        'views_count',
        'likes_count',
        'published_at',
        'meta_title',
        'meta_description',
        'og_title',
        'twitter_title',
        'howto_title',
        'howto_description',
        'faq_title',
        'toc_enabled',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'published_at' => 'datetime',
            'toc_items' => 'array',
            'content_rendered' => 'array',
            'toc_hash' => 'array',
            'toc_enabled' => 'boolean',
            'toc_generated_at' => 'datetime',
        ];
    }

    public function scopeReadable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'user_id');
    }

    /** The author's public profile (bio, avatar, socials), if one exists. */
    public function authorProfile(): HasOne
    {
        return $this->hasOne(BlogAuthorProfile::class, 'user_id', 'user_id');
    }

    /** The editorial reviewer (a user), when the post was vetted before publishing. */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'reviewed_by');
    }

    /** The reviewer's public author profile → schema.org Article.reviewedBy. */
    public function reviewerProfile(): HasOne
    {
        return $this->hasOne(BlogAuthorProfile::class, 'user_id', 'reviewed_by');
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

        // Inline images extracted from the post body by ContentImageIngestor.
        $this->addMediaCollection(config('blog-cms.media.content_collection', 'content'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $contentCollection = config('blog-cms.media.content_collection', 'content');
        $maxWidth = (int) config('blog-cms.media.content_image.max_width', 1600);

        // thumb/preview are only for cover + gallery imagery — NOT content images
        // (which use the width buckets below), so we don't generate unused files
        // for every content image and its WebP/AVIF variants.
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('cover_desktop', 'cover_mobile', 'gallery');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->performOnCollections('cover_desktop', 'cover_mobile', 'gallery');

        // Responsive srcset buckets for inline content images. Width-only, so the
        // natural aspect ratio is preserved; generated synchronously (nonQueued)
        // so the URLs are valid the moment the ingestor writes the srcset.

        // Width buckets are generated for the original content collection and for
        // the ShortPixel-converted WebP/AVIF variant collections (content-webp,
        // content-avif) — width-only, so each keeps its own format.
        $collections = [$contentCollection];
        foreach (\BlogCms\Support\ImageFormats::enabled() as $format) {
            $collections[] = $contentCollection.'-'.$format;
        }

        foreach (config('blog-cms.media.content_image.srcset_widths', []) as $width) {
            $width = (int) $width;
            if ($width < 1 || $width > $maxWidth) {
                continue;
            }

            $this->addMediaConversion('content-'.$width)
                ->width($width)
                ->performOnCollections(...$collections)
                ->nonQueued();
        }
    }
}
