<?php

namespace BlogCms\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the front-end URL of a blog page (post or category) in a given
 * locale. Slugs are translated per-locale (spatie/laravel-translatable) and
 * non-default locales are served under a path prefix (see LocaleManager).
 *
 * The app owns final URL structure, but the token API already commits to
 * `{prefix}/{category_slug}/{post_slug}` for posts and `{prefix}/{slug}` for
 * categories, so redirects, canonicals and the sitemap resolve against the same
 * contract. Kept in one place so the structure is changed once.
 */
class PageUrl
{
    public const TYPE_POST = 'post';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_PAGE = 'page';

    public const TYPES = [self::TYPE_POST, self::TYPE_CATEGORY, self::TYPE_PAGE];

    /**
     * Normalized path (no leading/trailing slash) for a type + id in a locale,
     * or null if the page can't be resolved in that locale.
     */
    public static function path(string $type, int|string $id, ?string $locale = null): ?string
    {
        $model = static::model($type, $id);

        return $model ? static::pathFor($model, $locale) : null;
    }

    /** Absolute URL for a type + id in a locale, or null when unresolvable. */
    public static function url(string $type, int|string $id, ?string $locale = null): ?string
    {
        $path = static::path($type, $id, $locale);

        return $path === null ? null : url($path);
    }

    /** Normalized locale-prefixed path for a resolved model instance. */
    public static function pathFor(Model $model, ?string $locale = null): ?string
    {
        $locale ??= LocaleManager::current();
        $prefix = LocaleManager::prefix($locale);

        if (static::isCategory($model)) {
            $slug = static::slug($model, $locale);

            return $slug === null ? null : static::join($prefix, $slug);
        }

        if (static::isPage($model)) {
            $slug = static::slug($model, $locale);
            if ($slug === null) {
                return null;
            }

            return static::join($prefix, static::pagePrefix($locale), $slug);
        }

        if (static::isPost($model)) {
            $category = $model->relationLoaded('category') ? $model->category : $model->category()->first();
            if (! $category) {
                return null;
            }

            $categorySlug = static::slug($category, $locale);
            $postSlug = static::slug($model, $locale);
            if ($categorySlug === null || $postSlug === null) {
                return null;
            }

            return static::join($prefix, $categorySlug, $postSlug);
        }

        return null;
    }

    /** Absolute URL for a resolved model instance in a locale. */
    public static function urlFor(Model $model, ?string $locale = null): ?string
    {
        $path = static::pathFor($model, $locale);

        return $path === null ? null : url($path);
    }

    /**
     * hreflang alternates: [locale => absolute url] for every enabled locale in
     * which the page has its own slug translation (no fallback).
     *
     * @return array<string, string>
     */
    public static function alternates(Model $model): array
    {
        $out = [];
        foreach (LocaleManager::all() as $locale) {
            if (! static::hasOwnSlug($model, $locale)) {
                continue;
            }
            if ($url = static::urlFor($model, $locale)) {
                $out[$locale] = $url;
            }
        }

        return $out;
    }

    /** The short type key ('post'|'category') for a model instance, or null. */
    public static function typeOf(Model $model): ?string
    {
        if (static::isPost($model)) {
            return self::TYPE_POST;
        }
        if (static::isCategory($model)) {
            return self::TYPE_CATEGORY;
        }
        if (static::isPage($model)) {
            return self::TYPE_PAGE;
        }

        return null;
    }

    public static function model(string $type, int|string $id): ?Model
    {
        $class = static::modelClass($type);

        return $class ? $class::find($id) : null;
    }

    /** Resolve a localized single-segment slug to a category, or null. */
    public static function categoryBySlug(string $slug, string $locale): ?Model
    {
        $class = config('blog-cms.models.category');

        return $class::query()->where("slug->{$locale}", $slug)->first();
    }

    /** Resolve a localized category+post slug pair to a post, or null. */
    public static function postBySlug(string $categorySlug, string $postSlug, string $locale): ?Model
    {
        $postClass = config('blog-cms.models.post');

        return $postClass::query()
            ->where("slug->{$locale}", $postSlug)
            ->whereHas('category', fn ($q) => $q->where("slug->{$locale}", $categorySlug))
            ->first();
    }

    /** Resolve a localized single-segment slug to a page, or null. */
    public static function pageBySlug(string $slug, string $locale): ?Model
    {
        $class = config('blog-cms.models.page');

        return $class::query()->where("slug->{$locale}", $slug)->first();
    }

    /**
     * The page URL prefix for a locale. `pages.url_prefix` may be a single
     * string (all locales) or a per-locale array (translated prefix, e.g.
     * ['en' => 'page', 'lt' => 'puslapis']); missing locales fall back to the
     * default locale's value, then the first, then ''.
     */
    public static function pagePrefix(?string $locale = null): string
    {
        $locale ??= LocaleManager::current();
        $prefix = config('blog-cms.pages.url_prefix', 'page');

        if (is_array($prefix)) {
            $prefix = $prefix[$locale] ?? $prefix[LocaleManager::default()] ?? reset($prefix) ?: '';
        }

        return trim((string) $prefix, '/');
    }

    public static function modelClass(string $type): ?string
    {
        return match ($type) {
            self::TYPE_POST => config('blog-cms.models.post'),
            self::TYPE_CATEGORY => config('blog-cms.models.category'),
            self::TYPE_PAGE => config('blog-cms.models.page'),
            default => null,
        };
    }

    /**
     * Flat option list of every selectable page, grouped for <optgroup>.
     * Labels use the default-locale title for stability.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function options(): array
    {
        $default = LocaleManager::default();
        $categoryClass = config('blog-cms.models.category');
        $postClass = config('blog-cms.models.post');

        $categories = $categoryClass::query()->get()
            ->map(fn ($c) => [
                'value' => self::TYPE_CATEGORY.':'.$c->getKey(),
                'label' => (string) ($c->getTranslation('title', $default, true) ?: '#'.$c->getKey()),
            ])
            ->sortBy('label')->values()->all();

        $posts = $postClass::query()->with('category')->get()
            ->map(function ($p) use ($default) {
                $title = (string) ($p->getTranslation('title', $default, true) ?: '#'.$p->getKey());
                $cat = $p->category ? $p->category->getTranslation('title', $default, true) : null;

                return [
                    'value' => self::TYPE_POST.':'.$p->getKey(),
                    'label' => $title.($cat ? ' — '.$cat : ''),
                ];
            })
            ->sortBy('label')->values()->all();

        $pageClass = config('blog-cms.models.page');
        $pages = $pageClass::query()->get()
            ->map(fn ($p) => [
                'value' => self::TYPE_PAGE.':'.$p->getKey(),
                'label' => (string) ($p->getTranslation('title', $default, true) ?: '#'.$p->getKey()),
            ])
            ->sortBy('label')->values()->all();

        return ['Categories' => $categories, 'Posts' => $posts, 'Pages' => $pages];
    }

    /**
     * Parse a "type:id" picker value into ['type' => ..., 'id' => ...],
     * or null when empty/invalid.
     *
     * @return array{type: string, id: int}|null
     */
    public static function parse(?string $value): ?array
    {
        if (! $value || ! str_contains($value, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $value, 2);

        if (! in_array($type, self::TYPES, true) || ! ctype_digit($id)) {
            return null;
        }

        return ['type' => $type, 'id' => (int) $id];
    }

    // ─── internals ───────────────────────────────────────────────

    /** Slug for a locale, falling back to the default-locale slug. */
    protected static function slug(Model $model, string $locale): ?string
    {
        $slug = $model->getTranslation('slug', $locale, true); // allow fallback

        return $slug ? trim($slug, '/') : null;
    }

    protected static function hasOwnSlug(Model $model, string $locale): bool
    {
        return (bool) $model->getTranslation('slug', $locale, false); // no fallback
    }

    protected static function join(string ...$parts): string
    {
        return implode('/', array_filter($parts, fn ($p) => $p !== '' && $p !== null));
    }

    protected static function isPost(Model $model): bool
    {
        return is_a($model, config('blog-cms.models.post'));
    }

    protected static function isCategory(Model $model): bool
    {
        return is_a($model, config('blog-cms.models.category'));
    }

    protected static function isPage(Model $model): bool
    {
        return is_a($model, config('blog-cms.models.page'));
    }
}
