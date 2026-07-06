<?php

namespace BlogCms\Support;

/**
 * SSRF guard for server-side image fetches. Content HTML (with <img> URLs)
 * arrives from the API, so a remote fetch must never be pointed at internal
 * infrastructure (cloud metadata, loopback, private networks).
 *
 * A URL is fetchable only if it is bare http/https with a host, passes an
 * optional operator allowlist, and every DNS record it resolves to is a public
 * IP. Callers must also disable HTTP redirects (a 3xx can otherwise bounce to a
 * blocked host after this check).
 */
class ImageUrlGuard
{
    public static function isFetchable(string $url): bool
    {
        return static::blockReason($url) === null;
    }

    /** Null when allowed, otherwise a short human-readable reason. */
    public static function blockReason(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'malformed url';
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return 'scheme not http(s)';
        }

        $host = trim($parts['host'], '[]'); // strip IPv6 literal brackets

        $allow = array_map('strtolower', (array) config('blog-cms.media.allowed_image_hosts', []));
        if ($allow !== [] && ! in_array(strtolower($host), $allow, true)) {
            return 'host not in allowlist';
        }

        $ips = static::resolve($host);
        if ($ips === []) {
            return 'host did not resolve';
        }

        foreach ($ips as $ip) {
            if (! static::isPublicIp($ip)) {
                return 'resolves to a private/reserved address';
            }
        }

        return null;
    }

    /** @return array<int, string> every A/AAAA address for the host (or the literal IP) */
    protected static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];
        $a = @gethostbynamel($host);
        if (is_array($a)) {
            $ips = array_merge($ips, $a);
        }

        $aaaa = @dns_get_record($host, DNS_AAAA);
        if (is_array($aaaa)) {
            foreach ($aaaa as $record) {
                if (! empty($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    protected static function isPublicIp(string $ip): bool
    {
        // Unwrap IPv4-mapped IPv6 (e.g. ::ffff:127.0.0.1).
        if (str_contains($ip, ':') && str_contains($ip, '.')) {
            $ip = substr($ip, strrpos($ip, ':') + 1);
        }

        $public = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );

        if ($public === false) {
            return false;
        }

        // Belt-and-suspenders for ranges filter flags don't consistently cover.
        if (str_starts_with($ip, '127.') || str_starts_with($ip, '0.')) {
            return false;
        }

        $lower = strtolower($ip);

        return $lower !== '::1' && $lower !== '::';
    }
}
