<?php

namespace BlogCms\Http\Controllers;

use BlogCms\Services\SearchService;
use Illuminate\Http\Request;

/**
 * Public site search. Renders the results page (blog::search); responses carry
 * a noindex,follow robots directive automatically via AddRobotsHeader because
 * the URL uses the `q` query parameter.
 *
 * Apps that own their `/?q=…` home route instead of using this one can call
 * SearchService directly and skip registering this route (config
 * blog-cms.search.route = null).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search)
    {
        $param = config('blog-cms.search.query_param', 'q');
        $term = trim((string) $request->query($param, ''));
        $locale = app()->getLocale();

        $results = $term === ''
            ? ['term' => '', 'posts' => null, 'categories' => collect(), 'tags' => collect()]
            : $search->search($term, $locale);

        return view('blog::search', [
            'term' => $term,
            'queryParam' => $param,
            'posts' => $results['posts'],
            'categories' => $results['categories'],
            'tags' => $results['tags'],
            'topTags' => $search->topTags(),
        ]);
    }
}
