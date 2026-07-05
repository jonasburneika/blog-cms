<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\Translation;
use BlogCms\Translation\DatabaseLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        $group = $request->get('group', 'frontend');

        // Get available locales from config or existing translations
        $locales = $this->getAvailableLocales();

        // Get available groups
        $groups = $this->getAvailableGroups();

        // Get file-based translations for this group
        $fileTranslations = $this->getFileTranslations($locale, $group);

        // Get DB translations (overrides)
        $dbTranslations = Translation::where('locale', $locale)
            ->where('group', $group)
            ->pluck('value', 'key')
            ->toArray();

        // Merge: show all keys from files + any extra DB keys
        $allKeys = array_unique(array_merge(array_keys($fileTranslations), array_keys($dbTranslations)));
        sort($allKeys);

        $translations = [];
        foreach ($allKeys as $key) {
            $translations[] = [
                'key' => $key,
                'file_value' => $fileTranslations[$key] ?? null,
                'db_value' => $dbTranslations[$key] ?? null,
                'is_overridden' => isset($dbTranslations[$key]),
            ];
        }

        return view('blog::admin.translations.index', compact(
            'translations', 'locale', 'group', 'locales', 'groups'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|max:10',
            'group' => 'required|string|max:100',
            'key' => 'required|string|max:500',
            'value' => 'nullable|string',
        ]);

        Translation::setTranslation(
            $validated['locale'],
            $validated['group'],
            $validated['key'],
            $validated['value']
        );

        DatabaseLoader::clearCache($validated['locale'], $validated['group']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Translation saved.']);
        }

        return redirect()->back()->with('success', 'Translation saved.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string',
            'translations.*.group' => 'required|string',
            'translations.*.key' => 'required|string',
            'translations.*.value' => 'nullable|string',
        ]);

        foreach ($validated['translations'] as $t) {
            if ($t['value'] !== null && $t['value'] !== '') {
                Translation::setTranslation($t['locale'], $t['group'], $t['key'], $t['value']);
            }
        }

        // Clear cache for the affected locale/group
        $locale = $validated['translations'][0]['locale'] ?? null;
        $group = $validated['translations'][0]['group'] ?? null;
        DatabaseLoader::clearCache($locale, $group);

        return redirect()->back()->with('success', 'Translations updated.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string',
            'group' => 'required|string',
            'key' => 'required|string',
        ]);

        Translation::where('locale', $validated['locale'])
            ->where('group', $validated['group'])
            ->where('key', $validated['key'])
            ->delete();

        DatabaseLoader::clearCache($validated['locale'], $validated['group']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Override removed, will use file translation.']);
        }

        return redirect()->back()->with('success', 'Translation override removed.');
    }

    // Import all file translations into DB for a given locale/group
    public function import(Request $request)
    {
        $locale = $request->get('locale', app()->getLocale());
        $group = $request->get('group', 'frontend');

        $fileTranslations = $this->getFileTranslations($locale, $group);
        $count = 0;

        foreach ($fileTranslations as $key => $value) {
            Translation::firstOrCreate(
                ['locale' => $locale, 'group' => $group, 'key' => $key],
                ['value' => $value]
            );
            $count++;
        }

        DatabaseLoader::clearCache($locale, $group);

        return redirect()->back()->with('success', "{$count} translations imported from files.");
    }

    // Add a new locale
    public function addLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|string|max:10|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
        ]);

        // Create at least one entry to register the locale
        Translation::firstOrCreate(
            ['locale' => $validated['locale'], 'group' => 'frontend', 'key' => '_locale_registered'],
            ['value' => $validated['locale']]
        );

        return redirect()->back()->with('success', "Locale '{$validated['locale']}' added.");
    }

    protected function getAvailableLocales(): array
    {
        $dbLocales = Translation::distinct()->pluck('locale')->toArray();
        $configLocale = [config('app.locale', 'en')];

        // Also check lang directory
        $langPath = lang_path();
        $dirLocales = [];
        if (File::isDirectory($langPath)) {
            foreach (File::directories($langPath) as $dir) {
                $dirLocales[] = basename($dir);
            }
        }

        return array_unique(array_merge($configLocale, $dirLocales, $dbLocales));
    }

    protected function getAvailableGroups(): array
    {
        $dbGroups = Translation::distinct()->pluck('group')->toArray();

        // Default groups
        $defaultGroups = ['frontend', 'admin', 'messages', 'validation', 'auth', 'pagination', 'passwords'];

        // Also check lang directory for files
        $langPath = lang_path(config('app.locale', 'en'));
        $fileGroups = [];
        if (File::isDirectory($langPath)) {
            foreach (File::files($langPath) as $file) {
                $fileGroups[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return array_values(array_unique(array_merge($defaultGroups, $fileGroups, $dbGroups)));
    }

    protected function getFileTranslations(string $locale, string $group): array
    {
        $path = lang_path("{$locale}/{$group}.php");
        if (File::exists($path)) {
            $translations = require $path;
            return is_array($translations) ? $this->flattenArray($translations) : [];
        }
        return [];
    }

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
