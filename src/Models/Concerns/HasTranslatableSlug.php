<?php

namespace BlogCms\Models\Concerns;

use BlogCms\Support\LocaleManager;
use Illuminate\Support\Str;

/**
 * Generates a slug per enabled locale from the translated title.
 *
 * Replaces spatie/laravel-sluggable (which doesn't combine cleanly with
 * translatable JSON slugs). Both `title` and `slug` must be listed in the
 * model's `$translatable`. Existing (manually set) locale slugs are never
 * overwritten, so published URLs stay stable.
 */
trait HasTranslatableSlug
{
    public static function bootHasTranslatableSlug(): void
    {
        static::saving(function ($model) {
            $model->generateTranslatableSlugs();
        });
    }

    public function generateTranslatableSlugs(): void
    {
        $source = $this->slugSourceField();

        foreach (LocaleManager::all() as $locale) {
            $title = $this->getTranslation($source, $locale, false);
            if (! $title) {
                continue;
            }

            // Respect a slug the editor set explicitly for this locale.
            if ($this->getTranslation('slug', $locale, false)) {
                continue;
            }

            $this->setTranslation('slug', $locale, $this->uniqueSlug(Str::slug($title), $locale));
        }
    }

    /**
     * Public entry for callers that set slugs explicitly (e.g. importers):
     * slugify $base and de-collide it against other rows for $locale. The
     * current model is excluded, so re-applying a model's own slug is stable.
     */
    public function uniqueSlugFor(string $base, string $locale): string
    {
        return $this->uniqueSlug(Str::slug($base), $locale);
    }

    protected function uniqueSlug(string $base, string $locale): string
    {
        $base = $base !== '' ? $base : 'n-a';
        $slug = $base;
        $suffix = 1;

        while ($this->slugExistsForLocale($slug, $locale)) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    protected function slugExistsForLocale(string $slug, string $locale): bool
    {
        return static::query()
            ->where("slug->{$locale}", $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->withoutGlobalScopes()
            ->exists();
    }

    /** Translatable attribute the slug is derived from. */
    protected function slugSourceField(): string
    {
        return 'title';
    }
}
