<?php

namespace BlogCms\Http\Middleware;

use BlogCms\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'API key required.'], 401);
        }

        $storedKey = Setting::get('api_key');

        if (! $storedKey || ! hash_equals($storedKey, $token)) {
            return response()->json(['error' => 'Invalid API key.'], 401);
        }

        return $next($request);
    }
}
