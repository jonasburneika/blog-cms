<?php

namespace BlogCms\Models\Concerns;

use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;

/**
 * Adds an optional canonical target (another live post/category) to a page.
 *
 * When no canonical is set the page is its own canonical. URLs resolve in the
 * current locale, so multilingual pages get a per-locale canonical. The app's
 * theme is expected to render
 * `<link rel="canonical" href="{{ $model->canonical_url }}">` plus hreflang
 * alternates from canonicalAlternates().
 */
trait HasCanonicalPage
{
    public function initializeHasCanonicalPage(): void
    {
        $this->mergeFillable(['canonical_type', 'canonical_id']);
    }

    public function hasCustomCanonical(): bool
    {
        return (bool) ($this->canonical_type && $this->canonical_id);
    }

    /**
     * Absolute canonical URL for the current locale. Falls back to the page's
     * own URL when no custom canonical is set (or the target won't resolve).
     */
    public function getCanonicalUrlAttribute(): ?string
    {
        $locale = LocaleManager::current();

        if ($this->hasCustomCanonical()) {
            return PageUrl::url($this->canonical_type, $this->canonical_id, $locale)
                ?? PageUrl::urlFor($this, $locale);
        }

        return PageUrl::urlFor($this, $locale);
    }

    /**
     * hreflang alternates for the canonical page: [locale => url].
     *
     * @return array<string, string>
     */
    public function canonicalAlternates(): array
    {
        if ($this->hasCustomCanonical()) {
            $target = PageUrl::model($this->canonical_type, $this->canonical_id);
            if ($target) {
                return PageUrl::alternates($target);
            }
        }

        return PageUrl::alternates($this);
    }

    /** True when the canonical points at another page (not self). */
    public function hasExternalCanonical(): bool
    {
        return $this->hasCustomCanonical()
            && PageUrl::url($this->canonical_type, $this->canonical_id) !== null;
    }

    /** The "type:id" picker value, or '' when self-canonical. */
    public function canonicalValue(): string
    {
        return $this->hasCustomCanonical() ? $this->canonical_type.':'.$this->canonical_id : '';
    }
}
