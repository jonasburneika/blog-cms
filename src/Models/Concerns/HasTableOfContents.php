<?php

namespace BlogCms\Models\Concerns;

use BlogCms\Support\LocaleManager;

/**
 * Read-side accessors for the per-locale table of contents populated by
 * BlogPostTocObserver. TOC data is stored as locale-keyed JSON maps
 * (toc_items / content_rendered), so every getter is locale-aware.
 */
trait HasTableOfContents
{
    /** TOC items for a locale (defaults to the current locale). */
    public function tableOfContents(?string $locale = null): array
    {
        $locale ??= LocaleManager::current();

        return ($this->toc_items ?? [])[$locale] ?? [];
    }

    /** Article HTML with heading ids for a locale; falls back to raw content. */
    public function renderedContent(?string $locale = null): string
    {
        $locale ??= LocaleManager::current();

        $rendered = ($this->content_rendered ?? [])[$locale] ?? null;
        if ($rendered !== null && $rendered !== '') {
            return $rendered;
        }

        return (string) $this->getTranslation('content', $locale, true);
    }

    /** Whether the TOC should be shown (enabled + at least two entries). */
    public function hasTableOfContents(?string $locale = null): bool
    {
        return (bool) $this->toc_enabled && count($this->tableOfContents($locale)) >= 2;
    }
}
