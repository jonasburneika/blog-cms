<?php

use BlogCms\Http\Controllers\Admin\BlogCategoryController;
use BlogCms\Http\Controllers\Admin\BlogPostBasedOnController;
use BlogCms\Http\Controllers\Admin\BlogPostCitationController;
use BlogCms\Http\Controllers\Admin\BlogPostController;
use BlogCms\Http\Controllers\Admin\BlogPostFaqController;
use BlogCms\Http\Controllers\Admin\BlogPostTopicController;
use BlogCms\Http\Controllers\Admin\BlogRedirectController;
use BlogCms\Http\Controllers\Admin\BlogPostHookController;
use BlogCms\Http\Controllers\Admin\BlogPostHowToStepController;
use BlogCms\Http\Controllers\Admin\DashboardController;
use BlogCms\Http\Controllers\Admin\GuestBlogRequestController;
use BlogCms\Http\Controllers\Admin\HookTemplateController;
use BlogCms\Http\Controllers\Admin\SettingController;
use BlogCms\Http\Controllers\Admin\TranslationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('categories', BlogCategoryController::class);
    Route::resource('posts', BlogPostController::class);
    Route::resource('redirects', BlogRedirectController::class)->except(['show']);
    Route::prefix('posts/{post}/faqs')->name('posts.faqs.')->group(function () {
        Route::get('/', [BlogPostFaqController::class, 'index'])->name('index');
        Route::post('/', [BlogPostFaqController::class, 'store'])->name('store');
        Route::put('/{faq}', [BlogPostFaqController::class, 'update'])->name('update');
        Route::delete('/{faq}', [BlogPostFaqController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostFaqController::class, 'reorder'])->name('reorder');
    });
    Route::prefix('posts/{post}/citations')->name('posts.citations.')->group(function () {
        Route::get('/', [BlogPostCitationController::class, 'index'])->name('index');
        Route::post('/', [BlogPostCitationController::class, 'store'])->name('store');
        Route::put('/{citation}', [BlogPostCitationController::class, 'update'])->name('update');
        Route::delete('/{citation}', [BlogPostCitationController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostCitationController::class, 'reorder'])->name('reorder');
    });
    Route::prefix('posts/{post}/topics')->name('posts.topics.')->group(function () {
        Route::get('/', [BlogPostTopicController::class, 'index'])->name('index');
        Route::post('/', [BlogPostTopicController::class, 'store'])->name('store');
        Route::put('/{topic}', [BlogPostTopicController::class, 'update'])->name('update');
        Route::delete('/{topic}', [BlogPostTopicController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostTopicController::class, 'reorder'])->name('reorder');
    });
    Route::prefix('posts/{post}/based-on')->name('posts.based-on.')->group(function () {
        Route::get('/', [BlogPostBasedOnController::class, 'index'])->name('index');
        Route::post('/', [BlogPostBasedOnController::class, 'store'])->name('store');
        Route::put('/{basedOn}', [BlogPostBasedOnController::class, 'update'])->name('update');
        Route::delete('/{basedOn}', [BlogPostBasedOnController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostBasedOnController::class, 'reorder'])->name('reorder');
    });
    Route::prefix('posts/{post}/howto-steps')->name('posts.howto-steps.')->group(function () {
        Route::get('/', [BlogPostHowToStepController::class, 'index'])->name('index');
        Route::post('/', [BlogPostHowToStepController::class, 'store'])->name('store');
        Route::put('/{step}', [BlogPostHowToStepController::class, 'update'])->name('update');
        Route::delete('/{step}', [BlogPostHowToStepController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostHowToStepController::class, 'reorder'])->name('reorder');
    });
    Route::prefix('guest-requests')->name('guest-requests.')->group(function () {
        Route::get('/', [GuestBlogRequestController::class, 'index'])->name('index');
        Route::get('/{guestBlogRequest}', [GuestBlogRequestController::class, 'show'])->name('show');
        Route::put('/{guestBlogRequest}/approve', [GuestBlogRequestController::class, 'approve'])->name('approve');
        Route::put('/{guestBlogRequest}/reject', [GuestBlogRequestController::class, 'reject'])->name('reject');
        Route::delete('/{guestBlogRequest}', [GuestBlogRequestController::class, 'destroy'])->name('destroy');
    });

    // Hook Templates (AJAX)
    Route::prefix('hook-templates')->name('hook-templates.')->group(function () {
        Route::get('/', [HookTemplateController::class, 'index'])->name('index');
        Route::post('/', [HookTemplateController::class, 'store'])->name('store');
        Route::get('/types', [HookTemplateController::class, 'types'])->name('types');
        Route::get('/{hookTemplate}', [HookTemplateController::class, 'show'])->name('show');
        Route::put('/{hookTemplate}', [HookTemplateController::class, 'update'])->name('update');
        Route::delete('/{hookTemplate}', [HookTemplateController::class, 'destroy'])->name('destroy');
    });

    // Translations
    Route::prefix('translations')->name('translations.')->group(function () {
        Route::get('/', [TranslationController::class, 'index'])->name('index');
        Route::post('/', [TranslationController::class, 'store'])->name('store');
        Route::put('/', [TranslationController::class, 'update'])->name('update');
        Route::delete('/', [TranslationController::class, 'destroy'])->name('destroy');
        Route::post('/import', [TranslationController::class, 'import'])->name('import');
        Route::post('/locale', [TranslationController::class, 'addLocale'])->name('add-locale');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/languages', [SettingController::class, 'updateLanguages'])->name('update.languages');
        Route::put('/general', [SettingController::class, 'updateGeneral'])->name('update.general');
        Route::put('/gsc', [SettingController::class, 'updateGsc'])->name('update.gsc');
        Route::put('/ga4', [SettingController::class, 'updateGa4'])->name('update.ga4');
        Route::post('/test-gsc', [SettingController::class, 'testGsc'])->name('test.gsc');
        Route::post('/test-ga4', [SettingController::class, 'testGa4'])->name('test.ga4');
        Route::post('/generate-api-key', [SettingController::class, 'generateApiKey'])->name('generate-api-key');
        Route::delete('/revoke-api-key', [SettingController::class, 'revokeApiKey'])->name('revoke-api-key');
    });

    // Blog Post Hooks (AJAX)
    Route::prefix('posts/{post}/hooks')->name('posts.hooks.')->group(function () {
        Route::get('/', [BlogPostHookController::class, 'index'])->name('index');
        Route::post('/', [BlogPostHookController::class, 'store'])->name('store');
        Route::put('/{hook}', [BlogPostHookController::class, 'update'])->name('update');
        Route::delete('/{hook}', [BlogPostHookController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [BlogPostHookController::class, 'reorder'])->name('reorder');
        Route::get('/{hook}/preview', [BlogPostHookController::class, 'preview'])->name('preview');
    });
});
