<?php

namespace BlogCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers (config: blog-cms.security_headers).
 *
 * Covers clickjacking (X-Frame-Options), MIME sniffing (X-Content-Type-Options),
 * referrer leakage (Referrer-Policy), unused browser APIs (Permissions-Policy),
 * protocol downgrade (HSTS, HTTPS only), and optionally CSP. Existing headers set
 * elsewhere are not overwritten, so a per-route override still wins.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('blog-cms.security_headers.enabled', true)) {
            return $response;
        }

        $config = config('blog-cms.security_headers', []);

        $simple = [
            'X-Frame-Options' => $config['x_frame_options'] ?? null,
            'X-Content-Type-Options' => $config['x_content_type_options'] ?? null,
            'Referrer-Policy' => $config['referrer_policy'] ?? null,
            'Permissions-Policy' => $config['permissions_policy'] ?? null,
            'Content-Security-Policy' => $config['content_security_policy'] ?? null,
            'Content-Security-Policy-Report-Only' => $config['content_security_policy_report_only'] ?? null,
        ];

        foreach ($simple as $header => $value) {
            if ($value !== null && $value !== '' && ! $response->headers->has($header)) {
                $response->headers->set($header, $value);
            }
        }

        // HSTS only makes sense — and is only honoured — over HTTPS.
        $hsts = $config['hsts'] ?? null;
        if ($hsts && $request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', $hsts);
        }

        return $response;
    }
}
