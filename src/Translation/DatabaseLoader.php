<?php

namespace BlogCms\Translation;

use BlogCms\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\FileLoader;

class DatabaseLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null): array
    {
        // Load file-based translations first (git-stored).
        $fileTranslations = parent::load($locale, $group, $namespace);

        // Namespaced (package) translations: return file translations as-is.
        if ($namespace && $namespace !== '*') {
            return $fileTranslations;
        }

        // Database translations (admin overrides) win over file translations.
        $dbTranslations = $this->loadFromDatabase($locale, $group);

        return array_replace_recursive($fileTranslations, $dbTranslations);
    }

    protected function loadFromDatabase(string $locale, string $group): array
    {
        return Cache::remember(
            "translations.{$locale}.{$group}",
            now()->addHours(24),
            function () use ($locale, $group) {
                return Translation::where('locale', $locale)
                    ->where('group', $group)
                    ->whereNotNull('value')
                    ->pluck('value', 'key')
                    ->toArray();
            }
        );
    }

    /**
     * Clear the translation cache for a specific locale/group, or all.
     */
    public static function clearCache(?string $locale = null, ?string $group = null): void
    {
        if ($locale && $group) {
            Cache::forget("translations.{$locale}.{$group}");
        } else {
            Cache::flush();
        }
    }
}
