<?php

namespace BlogCms\Http\Middleware;

use BlogCms\Models\BlogRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepts front-end page requests and 301/302s them when a redirect rule
 * matches the requested path. Prepended to the `web` group so it runs before
 * the app's own routes resolve.
 */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only plain page navigations are candidates. Skip admin/api/auth and
        // any non-GET/HEAD request so form posts and the panel are untouched.
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $path = trim($request->path(), '/'); // Laravel returns "/" as "/"

        if ($path === '' || $path === '/') {
            return $next($request);
        }

        $redirect = BlogRedirect::where('source_path', $path)->first();

        if ($redirect && ($target = $redirect->targetUrl())) {
            // Preserve the original query string on the way through.
            if ($query = $request->getQueryString()) {
                $target .= (str_contains($target, '?') ? '&' : '?').$query;
            }

            return redirect()->away($target, $redirect->status_code);
        }

        return $next($request);
    }

    protected function shouldSkip(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return true;
        }

        return $request->is('admin', 'admin/*', 'api', 'api/*', 'login', 'register', 'logout');
    }
}
