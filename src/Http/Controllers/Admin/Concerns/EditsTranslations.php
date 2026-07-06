<?php

namespace BlogCms\Http\Controllers\Admin\Concerns;

use BlogCms\Support\LocaleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Admin editing works one language at a time: a `locale` field (query param on
 * GET, hidden input on POST) selects which language's translations the form
 * reads and writes. Saving merges only that locale, preserving the others.
 */
trait EditsTranslations
{
    /** The locale currently being edited (validated against enabled locales). */
    protected function editingLocale(Request $request): string
    {
        $locale = (string) $request->input('locale', LocaleManager::default());

        return LocaleManager::isEnabled($locale) ? $locale : LocaleManager::default();
    }

    /**
     * Write submitted translatable fields into the given locale. `slug` is
     * skipped (auto-generated from the title). Only fields present in the
     * request are touched, so other locales are preserved.
     */
    protected function fillTranslations(Model $model, Request $request, string $locale): void
    {
        foreach ($model->translatable as $field) {
            if ($field === 'slug' || ! $request->exists($field)) {
                continue;
            }

            $model->setTranslation($field, $locale, $request->input($field));
        }
    }
}
