<?php

namespace BlogCms\Support;

use BlogCms\Models\Setting;
use Throwable;

/**
 * Single source of truth for which content locales the site offers and which
 * one is the default.
 *
 * Values are managed in the admin "Settings → Languages" page (stored in the
 * settings table, group `languages`) and fall back to config('blog-cms.locales'
 * / '.default_locale') before anything has been saved — or if the settings
 * table doesn't exist yet (e.g. during initial migration).
 */
class LocaleManager
{
    /** Per-request memo so we don't hit the settings table repeatedly. */
    protected static ?array $cache = null;

    /** Enabled content locales, default locale guaranteed first. */
    public static function all(): array
    {
        $locales = static::resolve()['locales'];

        // Default always present and first.
        $default = static::default();
        $locales = array_values(array_unique(array_merge([$default], $locales)));

        return $locales;
    }

    public static function default(): string
    {
        return static::resolve()['default'];
    }

    /** Run $callback with the app locale forced to $locale, then restore it. */
    public static function withLocale(string $locale, callable $callback): mixed
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return $callback();
        } finally {
            app()->setLocale($previous);
        }
    }

    public static function isEnabled(string $locale): bool
    {
        return in_array($locale, static::all(), true);
    }

    /** The active locale if it's enabled, otherwise the default. */
    public static function current(): string
    {
        $app = function_exists('app') ? app()->getLocale() : static::default();

        return static::isEnabled($app) ? $app : static::default();
    }

    public static function prefixDefault(): bool
    {
        return (bool) config('blog-cms.locale_url.prefix_default', false);
    }

    /** URL path prefix for a locale ('' when the default is unprefixed). */
    public static function prefix(string $locale): string
    {
        if ($locale === static::default() && ! static::prefixDefault()) {
            return '';
        }

        return $locale;
    }

    /** Detect a leading locale segment in a path; null when absent/unknown. */
    public static function detectPrefix(string $path): ?string
    {
        $segment = explode('/', trim($path, '/'), 2)[0] ?? '';

        return ($segment !== '' && static::isEnabled($segment)) ? $segment : null;
    }

    /** Clear the per-request memo (used after saving language settings). */
    public static function flush(): void
    {
        static::$cache = null;
    }

    protected static function resolve(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $configLocales = (array) config('blog-cms.locales', ['en']);
        $configDefault = (string) config('blog-cms.default_locale', $configLocales[0] ?? 'en');

        $locales = $configLocales;
        $default = $configDefault;

        try {
            $saved = Setting::get('locales');
            if (is_array($saved) && $saved !== []) {
                $locales = array_values(array_filter($saved, 'is_string'));
            }

            $savedDefault = Setting::get('default_locale');
            if (is_string($savedDefault) && $savedDefault !== '') {
                $default = $savedDefault;
            }
        } catch (Throwable) {
            // settings table not migrated yet — config fallback already set.
        }

        if (! in_array($default, $locales, true)) {
            $locales[] = $default;
        }

        return static::$cache = ['locales' => $locales, 'default' => $default];
    }
}
