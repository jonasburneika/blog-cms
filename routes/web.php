<?php

use BlogCms\Http\Controllers\FeedController;
use BlogCms\Http\Controllers\GuestBlogController;
use BlogCms\Http\Controllers\RobotsController;
use BlogCms\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// robots.txt (a static public/robots.txt, if present, is served first and wins).
if (config('blog-cms.robots_txt.enabled')) {
    Route::get('robots.txt', RobotsController::class);
}

// RSS feed of the newest posts.
if (config('blog-cms.rss.enabled') && ($feedRoute = config('blog-cms.rss.route'))) {
    Route::get($feedRoute, FeedController::class)->name('blog.feed');
}

// Public search page: GET /search?q=… (name blog.search). Disabled when
// config('blog-cms.search.route') is null — see BlogCmsServiceProvider.
if ($route = config('blog-cms.search.route')) {
    Route::get($route, SearchController::class)->name('blog.search');
}

// Public guest blogging (two-step): pitch, then tokenized article submission.
// Only registered when enabled; write endpoints are throttled.
if (config('blog-cms.guest_blogging.enabled')) {
    $prefix = trim((string) config('blog-cms.guest_blogging.route_prefix', 'guest-blog'), '/');
    $throttle = 'throttle:'.config('blog-cms.guest_blogging.throttle', '5,1');

    Route::prefix($prefix)->name('blog.guest.')->group(function () use ($throttle) {
        Route::get('/', [GuestBlogController::class, 'pitchForm'])->name('pitch');
        Route::post('/', [GuestBlogController::class, 'submitPitch'])->middleware($throttle)->name('pitch.store');
        Route::get('/submit/{token}', [GuestBlogController::class, 'articleForm'])->name('submit');
        Route::post('/submit/{token}', [GuestBlogController::class, 'submitArticle'])->middleware($throttle)->name('submit.store');
    });
}
