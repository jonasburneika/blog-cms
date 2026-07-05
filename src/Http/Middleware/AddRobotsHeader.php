<?php

namespace BlogCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddRobotsHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->is('admin/*') && !$request->is('login') && !$request->is('register')) {
            $response->headers->set('X-Robots-Tag', 'index, follow');
        } else {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
