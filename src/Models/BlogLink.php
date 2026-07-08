<?php

namespace BlogCms\Models;

use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A single link in a BlogLinkList. Its anchor text is translatable; its target
 * is either an internal page (post/category via PageUrl) or an external URL,
 * with a chosen target attribute and optional rel.
 */
class BlogLink extends Model
{
    use HasTranslations;

    public array $translatable = ['label'];

    public const TARGETS = ['_self', '_blank', '_parent', '_top'];

    protected $fillable = [
        'blog_link_list_id',
        'label',
        'link_type',
        'link_id',
        'url',
        'target',
        'rel',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(BlogLinkList::class, 'blog_link_list_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Target resolution ───────────────────────────────────────

    public function isInternal(): bool
    {
        return (bool) ($this->link_type && $this->link_id);
    }

    public function isExternal(): bool
    {
        return ! $this->isInternal() && ! empty($this->url);
    }

    /** Resolved href for a locale; '#' when an internal target no longer resolves. */
    public function href(?string $locale = null): string
    {
        if ($this->isInternal()) {
            return PageUrl::url($this->link_type, $this->link_id, $locale) ?? '#';
        }

        return $this->url ?: '#';
    }

    /** True when the link points somewhere real (not a dead internal target). */
    public function isResolvable(?string $locale = null): bool
    {
        return $this->href($locale) !== '#';
    }

    public function label(?string $locale = null): string
    {
        return (string) $this->getTranslation('label', $locale ?: app()->getLocale(), true);
    }

    /**
     * rel attribute to render: an explicit rel wins; otherwise external links
     * opened in a new tab get noopener noreferrer.
     */
    public function relAttribute(): ?string
    {
        if ($this->rel) {
            return $this->rel;
        }

        if ($this->target === '_blank' && $this->isExternal()) {
            return 'noopener noreferrer';
        }

        return null;
    }

    /** The "type:id" value for the admin page-select, or '' when external. */
    public function pageValue(): string
    {
        return $this->isInternal() ? $this->link_type.':'.$this->link_id : '';
    }
}
