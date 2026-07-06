<?php

namespace BlogCms\Models\Concerns;

use BlogCms\Support\PageUrl;

/**
 * Adds an optional canonical target (another live post/category) to a page.
 *
 * When no canonical is set the page is its own canonical. The app's theme is
 * expected to render `<link rel="canonical" href="{{ $model->canonical_url }}">`.
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
     * Absolute canonical URL. Falls back to the page's own URL when no custom
     * canonical is set (or the chosen target no longer resolves).
     */
    public function getCanonicalUrlAttribute(): ?string
    {
        if ($this->hasCustomCanonical()) {
            return PageUrl::url($this->canonical_type, $this->canonical_id) ?? PageUrl::urlFor($this);
        }

        return PageUrl::urlFor($this);
    }

    /** True when the canonical points at another page (not self). */
    public function hasExternalCanonical(): bool
    {
        return $this->hasCustomCanonical() && PageUrl::url($this->canonical_type, $this->canonical_id) !== null;
    }

    /** The "type:id" picker value, or '' when self-canonical. */
    public function canonicalValue(): string
    {
        return $this->hasCustomCanonical() ? $this->canonical_type.':'.$this->canonical_id : '';
    }
}
