<?php

namespace BlogCms\Support;

use Illuminate\Http\Request;

/**
 * Single source of truth for public SEO signals (robots directive + canonical),
 * shared by the AddRobotsHeader middleware (HTTP headers) and the
 * `<x-blog::seo-meta>` Blade component (<head> tags) so they never disagree.
 */
class SeoMeta
{
    /**
     * The robots directive for a request:
     *  - admin/auth paths      → noindex, nofollow
     *  - search-result pages   → noindex, follow  (out of index, links crawled)
     *  - everything else       → index, follow
     */
    public static function robots(Request $request): string
    {
        if (AdminPath::is($request) || $request->is('login') || $request->is('register')) {
            return 'noindex, nofollow';
        }

        // Sitemaps: referenced from robots.txt for crawlers, but kept out of the
        // search index (they should never appear as a result). Still crawlable.
        foreach ((array) config('blog-cms.robots_txt.noindex_paths', []) as $pattern) {
            if ($pattern !== '' && $request->is($pattern)) {
                return 'noindex, follow';
            }
        }

        if ($request->filled(config('blog-cms.search.query_param', 'q'))) {
            return 'noindex, follow';
        }

        return 'index, follow';
    }

    /**
     * Canonical URL for a request. Paginated pages (a `page` query param) point
     * at the primary page — the same URL with `page` stripped, other query
     * params preserved. Returns null when no pagination-driven canonical is
     * needed (the app supplies its own per-page canonical in that case).
     */
    public static function paginationCanonical(Request $request): ?string
    {
        if (! $request->has('page')) {
            return null;
        }

        $query = $request->query();
        unset($query['page']);

        $canonical = $request->url();
        if (! empty($query)) {
            $canonical .= '?' . http_build_query($query);
        }

        return $canonical;
    }
}
