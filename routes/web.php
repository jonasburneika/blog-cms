<?php

use BlogCms\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Public search page: GET /search?q=… (name blog.search). Disabled when
// config('blog-cms.search.route') is null — see BlogCmsServiceProvider.
if ($route = config('blog-cms.search.route')) {
    Route::get($route, SearchController::class)->name('blog.search');
}
