<?php

use BlogCms\Http\Controllers\Api\BlogCategoryController;
use BlogCms\Http\Controllers\Api\BlogPostController;
use BlogCms\Http\Controllers\Api\BlogPostFaqController;
use BlogCms\Http\Controllers\Api\BlogPostHowToStepController;
use BlogCms\Http\Controllers\Api\GatewayController;
use BlogCms\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware(\BlogCms\Http\Middleware\AuthenticateApiKey::class)->group(function () {
    // Single gateway endpoint (GET/POST only)
    Route::match(['get', 'post'], 'gateway', [GatewayController::class, 'handle'])->name('gateway');

    Route::get('categories', [BlogCategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [BlogCategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [BlogCategoryController::class, 'update'])->name('categories.update');

    Route::apiResource('posts', BlogPostController::class);

    Route::apiResource('posts.faqs', BlogPostFaqController::class)
        ->parameters(['faqs' => 'faq']);

    Route::apiResource('posts.howto-steps', BlogPostHowToStepController::class)
        ->parameters(['howto-steps' => 'howtoStep']);

    Route::post('posts/{post}/cover', [MediaController::class, 'uploadCover'])->name('posts.cover');
    Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
});
