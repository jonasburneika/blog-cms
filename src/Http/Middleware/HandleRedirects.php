<?php

namespace BlogCms\Http\Middleware;

use BlogCms\Models\BlogRedirect;
use BlogCms\Support\LocaleManager;
use BlogCms\Support\PageUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepts front-end page requests and 301/302s them when a redirect rule
 * matches the requested page. Prepended to the `web` group so it runs before
 * the app's own routes resolve.
 *
 * With per-locale slugs the path is resolved dynamically: strip an optional
 * leading locale prefix, resolve the remaining segment(s) to a post/category by
 * that locale's slug, then look for a redirect owned by that entity and send the
 * visitor to the target's URL in the same locale.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if ($path === '' || $path === '/') {
            return $next($request);
        }

        // Peel off a locale prefix (/lt/…); default locale is usually unprefixed.
        $locale = LocaleManager::detectPrefix($path);
        if ($locale !== null) {
            $path = trim(substr($path, strlen($locale)), '/');
        }
        $resolveLocale = $locale ?? LocaleManager::default();

        $entity = $this->resolveEntity($path, $resolveLocale);
        if (! $entity) {
            return $next($request);
        }

        $redirect = BlogRedirect::forSource(PageUrl::typeOf($entity), $entity->getKey());
        if ($redirect && ($target = $redirect->targetUrl($resolveLocale))) {
            if ($query = $request->getQueryString()) {
                $target .= (str_contains($target, '?') ? '&' : '?').$query;
            }

            return redirect()->away($target, $redirect->status_code);
        }

        return $next($request);
    }

    /** Resolve a locale-stripped path to a post (2 segments) or category (1). */
    protected function resolveEntity(string $path, string $locale): ?object
    {
        $segments = array_values(array_filter(explode('/', $path)));

        return match (count($segments)) {
            1 => PageUrl::categoryBySlug($segments[0], $locale),
            2 => PageUrl::postBySlug($segments[0], $segments[1], $locale),
            default => null,
        };
    }

    protected function shouldSkip(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return true;
        }

        return $request->is('admin', 'admin/*', 'api', 'api/*', 'login', 'register', 'logout');
    }
}
