<?php

namespace BlogCms\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Resolves the front-end URL of a blog page (post or category).
 *
 * The app owns final URL structure, but the token API (GatewayController)
 * already commits to `{category_slug}/{post_slug}` for posts and `{slug}` for
 * categories, so redirects and canonicals resolve against the same contract to
 * stay consistent. Kept in one place so the structure is changed once.
 */
class PageUrl
{
    public const TYPE_POST = 'post';
    public const TYPE_CATEGORY = 'category';

    public const TYPES = [self::TYPE_POST, self::TYPE_CATEGORY];

    /**
     * Normalized path (no leading/trailing slash) for a type + id, or null if
     * the page can't be resolved (missing record, post without a category).
     */
    public static function path(string $type, int|string $id): ?string
    {
        $model = static::model($type, $id);

        return $model ? static::pathFor($model) : null;
    }

    /** Absolute URL for a type + id, or null when unresolvable. */
    public static function url(string $type, int|string $id): ?string
    {
        $path = static::path($type, $id);

        return $path === null ? null : url($path);
    }

    /** Normalized path for a resolved model instance. */
    public static function pathFor(Model $model): ?string
    {
        if (static::isCategory($model)) {
            return $model->slug ? trim($model->slug, '/') : null;
        }

        if (static::isPost($model)) {
            $category = $model->relationLoaded('category') ? $model->category : $model->category()->first();

            if (! $category || ! $category->slug || ! $model->slug) {
                return null;
            }

            return trim($category->slug.'/'.$model->slug, '/');
        }

        return null;
    }

    /** Absolute URL for a resolved model instance. */
    public static function urlFor(Model $model): ?string
    {
        $path = static::pathFor($model);

        return $path === null ? null : url($path);
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

        return null;
    }

    /** Resolve a type + id back to its model instance (null when not found). */
    public static function model(string $type, int|string $id): ?Model
    {
        $class = static::modelClass($type);

        return $class ? $class::find($id) : null;
    }

    public static function modelClass(string $type): ?string
    {
        return match ($type) {
            self::TYPE_POST => config('blog-cms.models.post'),
            self::TYPE_CATEGORY => config('blog-cms.models.category'),
            default => null,
        };
    }

    /**
     * Flat option list of every selectable page, grouped for <optgroup>.
     * Value format: "post:{id}" / "category:{id}".
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function options(): array
    {
        $categoryClass = config('blog-cms.models.category');
        $postClass = config('blog-cms.models.post');

        $categories = $categoryClass::query()
            ->orderBy('title')
            ->get()
            ->map(fn ($c) => [
                'value' => self::TYPE_CATEGORY.':'.$c->getKey(),
                'label' => $c->title,
            ])
            ->all();

        $posts = $postClass::query()
            ->with('category:id,title')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => [
                'value' => self::TYPE_POST.':'.$p->getKey(),
                'label' => $p->title.($p->category ? ' — '.$p->category->title : ''),
            ])
            ->all();

        return [
            'Categories' => $categories,
            'Posts' => $posts,
        ];
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

    protected static function isPost(Model $model): bool
    {
        return is_a($model, config('blog-cms.models.post'));
    }

    protected static function isCategory(Model $model): bool
    {
        return is_a($model, config('blog-cms.models.category'));
    }
}
