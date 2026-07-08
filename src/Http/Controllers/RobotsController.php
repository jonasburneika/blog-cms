<?php

namespace BlogCms\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves GET /robots.txt (config blog-cms.robots_txt). Note: a static
 * public/robots.txt is served by the web server before this route, so it always
 * wins — remove it to let the package generate one.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        $disallow = (array) config('blog-cms.robots_txt.disallow', []);
        $queryParams = (array) config('blog-cms.robots_txt.disallow_query_params', []);

        if ($disallow || $queryParams) {
            foreach ($disallow as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            // Keep search + tag URLs (?q=…) out of the crawl — as the first param
            // (?q=) and as a later param (&q=).
            foreach ($queryParams as $param) {
                $lines[] = 'Disallow: /*?'.$param.'=';
                $lines[] = 'Disallow: /*&'.$param.'=';
            }
        } else {
            $lines[] = 'Allow: /';
        }

        foreach ((array) config('blog-cms.robots_txt.extra', []) as $extra) {
            $lines[] = $extra;
        }

        $sitemap = config('blog-cms.robots_txt.sitemap_url') ?: url('/sitemap.xml');
        $lines[] = '';
        $lines[] = 'Sitemap: '.$sitemap;

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
