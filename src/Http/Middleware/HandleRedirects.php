<?php

namespace BlogCms\Http\Middleware;

use BlogCms\Support\AdminPath;

use BlogCms\Models\BlogRedirect;
use BlogCms\Models\BlogUrlHistory;
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

        $fullPath = trim($request->path(), '/');
        if ($fullPath === '') {
            return $next($request);
        }

        // Peel off a locale prefix (/lt/…); default locale is usually unprefixed.
        $locale = LocaleManager::detectPrefix($fullPath);
        $rest = $locale !== null ? trim(substr($fullPath, strlen($locale)), '/') : $fullPath;
        $resolveLocale = $locale ?? LocaleManager::default();

        // Live page: honour a manual entity→entity redirect, otherwise serve it.
        $entity = $this->resolveEntity($rest, $resolveLocale);
        if ($entity) {
            $redirect = BlogRedirect::forSource(PageUrl::typeOf($entity), $entity->getKey());
            if ($redirect && ($target = $redirect->targetUrl($resolveLocale))) {
                return $this->redirectTo($target, $redirect->status_code, $request);
            }

            return $next($request);
        }

        // Dead path: an old URL from a slug change → send it to the current one.
        $history = BlogUrlHistory::firstWhere('old_path', $fullPath);
        if ($history && ($target = $history->targetUrl())) {
            return $this->redirectTo($target, $history->status_code, $request);
        }

        return $next($request);
    }

    protected function redirectTo(string $target, int $status, Request $request): Response
    {
        if ($query = $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?').$query;
        }

        return redirect()->away($target, $status);
    }

    /** Resolve a locale-stripped path to a category (1 seg), post (2), or page. */
    protected function resolveEntity(string $path, string $locale): ?object
    {
        $segments = array_values(array_filter(explode('/', $path)));
        $pagePrefix = PageUrl::pagePrefix($locale);

        // Prefixed pages: {pagePrefix}/{slug}.
        if ($pagePrefix !== '' && count($segments) === 2 && $segments[0] === $pagePrefix) {
            if ($page = PageUrl::pageBySlug($segments[1], $locale)) {
                return $page;
            }
        }

        return match (count($segments)) {
            // Root-level pages share the 1-segment space with categories → category wins.
            1 => PageUrl::categoryBySlug($segments[0], $locale)
                ?: ($pagePrefix === '' ? PageUrl::pageBySlug($segments[0], $locale) : null),
            2 => PageUrl::postBySlug($segments[0], $segments[1], $locale),
            default => null,
        };
    }

    protected function shouldSkip(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return true;
        }

        return AdminPath::is($request) || $request->is('api', 'api/*', 'login', 'register', 'logout');
    }
}
