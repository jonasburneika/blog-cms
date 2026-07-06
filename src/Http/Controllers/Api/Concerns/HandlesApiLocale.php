<?php

namespace BlogCms\Http\Controllers\Api\Concerns;

use BlogCms\Models\BlogCategory;
use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Locale helpers for the token API. Content is stored per-locale
 * (spatie/laravel-translatable); a `locale` request param selects which
 * language to read/write. Writes land under that locale (preserving others);
 * reads flatten translatable JSON to plain strings so existing consumers keep
 * seeing `data.title` as a string.
 */
trait HandlesApiLocale
{
    /** Requested locale if enabled, else the site default. */
    protected function apiLocale(Request $request): string
    {
        $locale = (string) $request->input('locale', LocaleManager::default());

        return LocaleManager::isEnabled($locale) ? $locale : LocaleManager::default();
    }

    /**
     * Serialize a model with translatable fields flattened to one locale, plus
     * the locale-aware URL and hreflang alternates.
     */
    protected function presentInLocale(Model $model, string $locale): array
    {
        $data = $model->toArray();

        foreach ($model->translatable as $field) {
            $data[$field] = $model->getTranslation($field, $locale, true);
        }

        // Flatten an eager-loaded category too.
        if (isset($data['category']) && $model->relationLoaded('category') && $model->category) {
            foreach ($model->category->translatable as $field) {
                $data['category'][$field] = $model->category->getTranslation($field, $locale, true);
            }
        }

        return array_merge($data, [
            'url' => PageUrl::urlFor($model, $locale),
            'alternates' => PageUrl::alternates($model),
        ]);
    }

    /** Find a category by id, or by slug/title in the given (or default) locale. */
    protected function findCategoryByRef(string $value, string $locale): ?BlogCategory
    {
        $default = LocaleManager::default();

        return BlogCategory::query()
            ->where("slug->{$locale}", $value)
            ->orWhere("title->{$locale}", $value)
            ->orWhere("slug->{$default}", $value)
            ->orWhere("title->{$default}", $value)
            ->first();
    }
}
