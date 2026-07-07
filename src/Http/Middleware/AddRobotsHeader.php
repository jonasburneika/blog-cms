<?php

namespace BlogCms\Http\Middleware;

use BlogCms\Support\SeoMeta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SEO response headers for public pages, applied to the whole `web` group.
 *
 * - Admin/auth paths → noindex,nofollow.
 * - Search-result pages (a `q` query param) → noindex,follow.
 * - Paginated pages get a `Link: rel="canonical"` header pointing at the primary
 *   (first) page so page 2..N don't compete with page 1 in the index.
 * - Everything else → index,follow.
 *
 * The <head> equivalents (meta robots, canonical, Open Graph, Twitter card) are
 * emitted by the <x-blog::seo-meta> component, which shares the same logic via
 * BlogCms\Support\SeoMeta.
 */
class AddRobotsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', SeoMeta::robots($request));

        if ($canonical = SeoMeta::paginationCanonical($request)) {
            $response->headers->set('Link', '<' . $canonical . '>; rel="canonical"', false);
        }

        return $response;
    }
}
