<?php

namespace BlogCms;

use BlogCms\Http\Middleware\AddRobotsHeader;
use BlogCms\Http\Middleware\AuthenticateApiKey;
use BlogCms\Http\Middleware\HandleRedirects;
use BlogCms\Http\Middleware\SecurityHeaders;
use BlogCms\Models\BlogRedirect;
use BlogCms\Models\BlogUrlHistory;
use BlogCms\Observers\BlogPostTocObserver;
use BlogCms\Observers\ContentImageObserver;
use BlogCms\Services\UrlHistoryRecorder;
use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;
use BlogCms\Translation\DatabaseLoader;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BlogCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blog-cms.php', 'blog-cms');

        // DB-backed translation loader: file translations first, admin overrides on top.
        $this->app->extend('translation.loader', function ($loader, $app) {
            return new DatabaseLoader($app['files'], $app['path.lang']);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'blog');

        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerModelObservers();
        $this->shareLocales();
        $this->registerComponents();
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerSchedule();
    }

    /** Register the package's artisan commands (console only). */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([\BlogCms\Console\AutoSeoSyncCommand::class]);
        }
    }

    /**
     * Schedule the AutoSEO sync when blog-cms.autoseo.sync_schedule is set —
     * either a preset (daily, hourly, twiceDaily, …) or a five-part cron
     * expression. Runs the command (not the queued job) so no queue worker is
     * required; the host app only needs the standard Laravel scheduler.
     */
    protected function registerSchedule(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $expression = trim((string) config('blog-cms.autoseo.sync_schedule', ''));
            if ($expression === '') {
                return;
            }

            $event = $schedule->command('blog-cms:autoseo-sync')
                ->withoutOverlapping()
                ->runInBackground();

            $presets = [
                'everyFifteenMinutes', 'everyThirtyMinutes', 'hourly', 'everyTwoHours',
                'everyThreeHours', 'everyFourHours', 'everySixHours', 'daily',
                'twiceDaily', 'weekly', 'monthly',
            ];

            if (str_contains($expression, ' ')) {
                $event->cron($expression);
            } elseif (in_array($expression, $presets, true)) {
                $event->{$expression}();
            } else {
                $event->daily();
            }
        });
    }

    /** Expose the package's anonymous Blade components as <x-blog::name>. */
    protected function registerComponents(): void
    {
        Blade::anonymousComponentNamespace('blog::components', 'blog');
    }

    /** Expose the enabled/default content locales to every admin view. */
    protected function shareLocales(): void
    {
        View::share('blogLocales', LocaleManager::all());
        View::share('blogDefaultLocale', LocaleManager::default());
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('blog.apikey', AuthenticateApiKey::class);
        // Serve redirect rules before the app's own routes resolve.
        $router->prependMiddlewareToGroup('web', HandleRedirects::class);
        // X-Robots-Tag on every web response (noindex,nofollow for admin/auth paths).
        $router->pushMiddlewareToGroup('web', AddRobotsHeader::class);
        // Baseline security headers (HSTS, X-Frame-Options, nosniff, …).
        $router->pushMiddlewareToGroup('web', SecurityHeaders::class);
    }

    /** Drop redirects that point at (or come from) a deleted page. */
    protected function registerModelObservers(): void
    {
        $postClass = config('blog-cms.models.post');
        $categoryClass = config('blog-cms.models.category');
        $pageClass = config('blog-cms.models.page');

        $postClass::deleted(function (Model $post) {
            BlogRedirect::purgeReferences(PageUrl::TYPE_POST, $post->getKey());
            BlogUrlHistory::purgeTarget(PageUrl::TYPE_POST, $post->getKey());
        });
        $categoryClass::deleted(function (Model $category) {
            BlogRedirect::purgeReferences(PageUrl::TYPE_CATEGORY, $category->getKey());
            BlogUrlHistory::purgeTarget(PageUrl::TYPE_CATEGORY, $category->getKey());
        });
        $pageClass::deleted(function (Model $page) {
            BlogRedirect::purgeReferences(PageUrl::TYPE_PAGE, $page->getKey());
            BlogUrlHistory::purgeTarget(PageUrl::TYPE_PAGE, $page->getKey());
        });

        // Record old URLs automatically when a published slug changes.
        $postClass::saved(fn (Model $post) => app(UrlHistoryRecorder::class)->recordPostChange($post));
        $categoryClass::saved(fn (Model $category) => app(UrlHistoryRecorder::class)->recordCategoryChange($category));
        $pageClass::saved(fn (Model $page) => app(UrlHistoryRecorder::class)->recordPageChange($page));

        // Regenerate the per-locale table of contents when content changes.
        $postClass::observe(BlogPostTocObserver::class);

        // Pull inline content images into MediaLibrary after save.
        $postClass::observe(ContentImageObserver::class);
    }

    protected function registerRoutes(): void
    {
        // Admin needs the full web stack (session, csrf, auth). The admin.php file
        // keeps its own ->prefix('admin')->middleware('auth')->name('admin.') wrapper.
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
        });

        // Public front-end routes (search). The file no-ops when the search route
        // is disabled via config('blog-cms.search.route').
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        // Stateless JSON API under /api. The api.php file applies the api-key
        // middleware (blog.apikey) around its route group.
        Route::middleware('api')->prefix('api')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/blog-cms.php' => config_path('blog-cms.php'),
        ], 'blog-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/blog'),
        ], 'blog-views');

        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/blog'),
        ], 'blog-assets');

        // Example 404 into the app's error views (Laravel resolves errors.404
        // from the app namespace, so it must live outside the blog:: namespace).
        $this->publishes([
            __DIR__.'/../resources/views/errors/404.blade.php' => resource_path('views/errors/404.blade.php'),
        ], 'blog-frontend');
    }
}
