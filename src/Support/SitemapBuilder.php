<?php

namespace BlogCms\Support;

use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the blog portion of the sitemap (categories + posts).
 *
 * Ported from the app's routes/web.php `/sitemap.xml` closure. The app owns
 * URL structure (routes, slugs, prefixes), so it passes in resolvers rather
 * than core hardcoding paths. Home/about/calculator entries are NOT added
 * here — the app appends those itself before/after calling make().
 */
class SitemapBuilder
{
    /**
     * @param  callable(BlogPost): string  $postUrl
     * @param  callable(BlogCategory): string  $categoryUrl
     */
    public static function make(callable $postUrl, callable $categoryUrl): Sitemap
    {
        $sitemap = Sitemap::create();

        BlogCategory::query()
            ->where('status', 'active')
            ->hasReadablePosts()
            ->get()
            ->each(function (BlogCategory $category) use ($sitemap, $categoryUrl) {
                $sitemap->add(Url::create($categoryUrl($category))
                    ->setLastModificationDate($category->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.8));
            });

        BlogPost::query()
            ->with('category')
            ->readable()
            ->get()
            ->each(function (BlogPost $post) use ($sitemap, $postUrl) {
                if (! $post->category) {
                    return;
                }

                $sitemap->add(Url::create($postUrl($post))
                    ->setLastModificationDate($post->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.7));
            });

        static::addPages($sitemap);

        return $sitemap;
    }

    /** Add published, indexable simple pages (URLs resolved via PageUrl). */
    protected static function addPages(Sitemap $sitemap): void
    {
        $pageClass = config('blog-cms.models.page');

        $pageClass::query()
            ->published()
            ->where('is_indexable', true)
            ->get()
            ->each(function (Model $page) use ($sitemap) {
                if ($url = PageUrl::urlFor($page)) {
                    $sitemap->add(Url::create($url)
                        ->setLastModificationDate($page->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
                        ->setPriority(0.4));
                }
            });
    }

    /**
     * Multilingual sitemap: one entry per enabled locale for each category/post,
     * with hreflang alternates cross-linking the translations. URLs come from
     * PageUrl, so the app doesn't pass resolvers here.
     */
    public static function makeMultilingual(): Sitemap
    {
        $sitemap = Sitemap::create();
        $locales = LocaleManager::all();

        BlogCategory::query()
            ->where('status', 'active')
            ->hasReadablePosts()
            ->get()
            ->each(fn (BlogCategory $category) => static::addLocalized($sitemap, $category, $locales, 0.8, Url::CHANGE_FREQUENCY_WEEKLY));

        BlogPost::query()
            ->with('category')
            ->readable()
            ->get()
            ->each(function (BlogPost $post) use ($sitemap, $locales) {
                if ($post->category) {
                    static::addLocalized($sitemap, $post, $locales, 0.7, Url::CHANGE_FREQUENCY_MONTHLY);
                }
            });

        $pageClass = config('blog-cms.models.page');
        $pageClass::query()
            ->published()
            ->where('is_indexable', true)
            ->get()
            ->each(fn (Model $page) => static::addLocalized($sitemap, $page, $locales, 0.4, Url::CHANGE_FREQUENCY_YEARLY));

        return $sitemap;
    }

    /** Add one <url> per locale for a model, each carrying all hreflang alternates. */
    protected static function addLocalized(Sitemap $sitemap, Model $model, array $locales, float $priority, string $frequency): void
    {
        $alternates = PageUrl::alternates($model);

        foreach ($locales as $locale) {
            $url = PageUrl::urlFor($model, $locale);
            if (! $url) {
                continue;
            }

            $tag = Url::create($url)
                ->setLastModificationDate($model->updated_at)
                ->setChangeFrequency($frequency)
                ->setPriority($priority);

            foreach ($alternates as $altLocale => $altUrl) {
                $tag->addAlternate($altUrl, $altLocale);
            }

            $sitemap->add($tag);
        }
    }
}
