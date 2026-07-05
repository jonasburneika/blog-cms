<?php

namespace BlogCms\Support;

use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

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

        return $sitemap;
    }
}
