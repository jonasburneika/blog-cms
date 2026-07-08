<?php

namespace BlogCms\Http\Controllers;

use BlogCms\Models\Setting;
use BlogCms\Support\LocaleManager;
use Illuminate\Http\Response;

/**
 * RSS 2.0 feed of the newest published posts (config blog-cms.rss) so readers
 * can subscribe. Posts without a category are skipped (no resolvable URL).
 */
class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $locale = LocaleManager::current() ?: LocaleManager::default();
        $postClass = config('blog-cms.models.post');

        $posts = $postClass::query()
            ->with('category')
            ->readable()
            ->latest('published_at')
            ->limit((int) config('blog-cms.rss.limit', 20))
            ->get()
            ->filter(fn ($post) => $post->category !== null)
            ->values();

        $xml = view('blog::feed.rss', [
            'posts' => $posts,
            'locale' => $locale,
            'title' => config('blog-cms.rss.title') ?: Setting::get('site_name', config('app.name')),
            'description' => config('blog-cms.rss.description') ?: Setting::get('site_description', ''),
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
