<?php

namespace BlogCms;

use BlogCms\Http\Middleware\AddRobotsHeader;
use BlogCms\Http\Middleware\AuthenticateApiKey;
use BlogCms\Translation\DatabaseLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
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
        $this->registerPublishing();
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('blog.apikey', AuthenticateApiKey::class);
        // X-Robots-Tag on every web response (noindex,nofollow for admin/auth paths).
        $router->pushMiddlewareToGroup('web', AddRobotsHeader::class);
    }

    protected function registerRoutes(): void
    {
        // Admin needs the full web stack (session, csrf, auth). The admin.php file
        // keeps its own ->prefix('admin')->middleware('auth')->name('admin.') wrapper.
        Route::middleware('web')->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/admin.php');
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
    }
}
