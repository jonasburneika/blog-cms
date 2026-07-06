<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Public-facing author data (bio, role, avatar, socials) attached to an app
 * user. Feeds the Article schema's author node and the app-rendered author page
 * (headless: the app owns the route/template; this model provides the data).
 */
class BlogAuthorProfile extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    /** Bio is stored per locale. */
    public array $translatable = ['bio'];

    protected $fillable = [
        'user_id',
        'slug',
        'display_name',
        'role',
        'bio',
        'social_links',
        'is_indexable',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'is_indexable' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('blog-cms.models.user'), 'user_id');
    }

    /** All posts written by this author. */
    public function posts(): HasMany
    {
        return $this->hasMany(config('blog-cms.models.post'), 'user_id', 'user_id');
    }

    public function publishedPosts()
    {
        return $this->posts()->readable()->latest('published_at');
    }

    // ─── Presentation ────────────────────────────────────────────

    /** Byline name: explicit override, else the linked user's name. */
    public function name(): string
    {
        return $this->display_name ?: ($this->user?->name ?? '');
    }

    public function avatarUrl(string $conversion = ''): ?string
    {
        return $this->getFirstMediaUrl('avatar', $conversion) ?: null;
    }

    /** @return array<int, array{label:string,url:string}> */
    public function socialLinks(): array
    {
        return array_values(array_filter(
            (array) $this->social_links,
            fn ($l) => ! empty($l['url']),
        ));
    }

    /** Path to the author page (app owns the actual route). */
    public function pagePath(): string
    {
        return trim(config('blog-cms.author_url.prefix', 'author').'/'.$this->slug, '/');
    }

    public function pageUrl(): string
    {
        return url($this->pagePath());
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->where('is_indexable', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(160)->height(160)->sharpen(10);
    }
}
