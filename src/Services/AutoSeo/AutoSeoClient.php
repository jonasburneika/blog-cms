<?php

namespace BlogCms\Services\AutoSeo;

use BlogCms\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the AutoSEO REST API v1 (https://autoseo.5z.lt/api/v1).
 *
 * Every value that can differ per install (base URL, API token, site id) is
 * resolved from the settings table first, falling back to config/blog-cms.php,
 * so credentials can be managed from the admin UI without touching .env.
 * The importers code against this class's public method signatures.
 */
class AutoSeoClient
{
    /** All endpoints live under this prefix on the deployment host. */
    protected const API_PREFIX = '/api/v1';

    /** Bearer token, site id and base URL must all be present to talk to the API. */
    public function isConfigured(): bool
    {
        return $this->apiToken() !== '' && $this->siteId() !== '' && $this->rawBaseUrl() !== '';
    }

    /** Resolved base URL with any trailing slash stripped. */
    public function baseUrl(): string
    {
        return rtrim($this->rawBaseUrl(), '/');
    }

    /** Host part of the base URL (used to allowlist which image hosts we download from). */
    public function baseHost(): string
    {
        return (string) (parse_url($this->baseUrl(), PHP_URL_HOST) ?: '');
    }

    /**
     * Resolve a possibly-relative URL against the base URL.
     * Absolute URLs pass through untouched; scheme-relative (//host/…) inherit
     * the base scheme; root-relative and relative paths hang off the base host.
     */
    public function absoluteUrl(?string $url): ?string
    {
        $url = $url !== null ? trim($url) : '';
        if ($url === '') {
            return null;
        }

        // Already absolute (http://, https://, data:, …).
        if (preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url) || str_starts_with($url, 'data:')) {
            return $url;
        }

        $base = $this->baseUrl();

        // Scheme-relative: //cdn.example.com/img.png
        if (str_starts_with($url, '//')) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';

            return $scheme.':'.$url;
        }

        // Root-relative: /media/img.png
        if (str_starts_with($url, '/')) {
            return $base.$url;
        }

        // Relative: media/img.png
        return $base.'/'.$url;
    }

    public function ping(): array
    {
        return $this->request('GET', '/ping');
    }

    /** Site record — unwraps the top-level `data` envelope. */
    public function site(): array
    {
        $response = $this->request('GET', '/sites/'.$this->siteId());

        return $response['data'] ?? $response;
    }

    /** @return array<int,array<string,mixed>> */
    public function categories(): array
    {
        return $this->siteList('categories', 'categories');
    }

    /** @return array<int,array<string,mixed>> */
    public function authors(): array
    {
        return $this->siteList('authors', 'authors');
    }

    /** @return array<int,array<string,mixed>> */
    public function citations(): array
    {
        return $this->siteList('citations', 'citations');
    }

    /** @return array<int,array<string,mixed>> */
    public function media(): array
    {
        return $this->siteList('media', 'media_assets');
    }

    /** @return array<int,array<string,mixed>> */
    public function posts(): array
    {
        return $this->siteList('posts', 'posts');
    }

    /** Full post detail. */
    public function post(int $id): array
    {
        return $this->request('GET', '/posts/'.$id);
    }

    /** Full media-asset detail (url, path, alt_text, checksum, …). */
    public function mediaAsset(int $id): array
    {
        return $this->request('GET', '/media/'.$id);
    }

    /**
     * Download the raw bytes of a media file with the bearer token attached.
     * Accepts any content type, enforces the configured timeout and refuses
     * payloads larger than $maxBytes (checked against Content-Length up front
     * and the actual body afterwards).
     */
    public function downloadFile(string $url, int $maxBytes = 15_728_640): string
    {
        $this->assertConfigured();

        $absolute = $this->absoluteUrl($url);
        if ($absolute === null) {
            throw new AutoSeoException('Cannot download AutoSEO media: empty or invalid URL.');
        }

        try {
            $request = Http::timeout($this->timeout())
                ->withOptions(['allow_redirects' => false])
                ->withHeaders(['Accept' => '*/*']);

            // Only present the API credential to the AutoSEO host itself —
            // media may live on a CDN/object store that must not see the token.
            $host = strtolower((string) parse_url($absolute, PHP_URL_HOST));
            if ($host !== '' && $host === strtolower($this->baseHost())) {
                $request = $request->withToken($this->apiToken());
            }

            $response = $request->get($absolute);
        } catch (ConnectionException $e) {
            throw new AutoSeoException("Could not connect to AutoSEO to download {$absolute}: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new AutoSeoException("Failed to download AutoSEO media {$absolute}: HTTP {$response->status()}.");
        }

        $declared = (int) $response->header('Content-Length');
        if ($declared > $maxBytes) {
            throw new AutoSeoException("AutoSEO media {$absolute} is too large ({$declared} bytes, limit {$maxBytes}).");
        }

        $body = $response->body();
        if (strlen($body) > $maxBytes) {
            throw new AutoSeoException('AutoSEO media '.$absolute.' exceeds the '.$maxBytes.'-byte download limit.');
        }

        return $body;
    }

    /**
     * Fetch a site-scoped list endpoint and return the array under $key.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function siteList(string $path, string $key): array
    {
        $response = $this->request('GET', '/sites/'.$this->siteId().'/'.$path);

        $items = $response[$key] ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * Perform an authenticated JSON request against the API and return the
     * decoded body. Throws AutoSeoException on misconfiguration, connection
     * failure or any non-2xx response.
     *
     * @param  array<string,mixed>  $query
     * @return array<string,mixed>
     */
    protected function request(string $method, string $path, array $query = []): array
    {
        $this->assertConfigured();

        $url = $this->baseUrl().self::API_PREFIX.'/'.ltrim($path, '/');

        try {
            $response = Http::withToken($this->apiToken())
                ->timeout($this->timeout())
                ->acceptJson()
                ->send(strtoupper($method), $url, ['query' => $query]);
        } catch (ConnectionException $e) {
            throw new AutoSeoException("Could not connect to AutoSEO at {$url}: {$e->getMessage()}", 0, $e);
        }

        if (! $response->successful()) {
            throw new AutoSeoException($this->errorMessage($method, $url, $response));
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /** Build a human-readable error, folding in the RFC-7807 Problem `detail` when present. */
    protected function errorMessage(string $method, string $url, Response $response): string
    {
        $message = 'AutoSEO API '.strtoupper($method)." {$url} failed with HTTP {$response->status()}";

        $body = $response->json();
        if (is_array($body)) {
            $detail = $body['detail'] ?? $body['title'] ?? null;
            if (is_string($detail) && $detail !== '') {
                $message .= ': '.$detail;
            }
        }

        return $message.'.';
    }

    /** @throws AutoSeoException when required credentials are missing. */
    protected function assertConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw new AutoSeoException(
            'AutoSEO is not configured. Set the base URL, API token and site id via the '
            .'settings (autoseo_base_url, autoseo_api_token, autoseo_site_id) or in config/blog-cms.php '
            .'(blog-cms.autoseo.base_url / .api_token / .site_id, e.g. AUTOSEO_BASE_URL, '
            .'AUTOSEO_API_TOKEN, AUTOSEO_SITE_ID).'
        );
    }

    protected ?string $resolvedBaseUrl = null;

    protected ?string $resolvedApiToken = null;

    protected ?string $resolvedSiteId = null;

    /**
     * Settings take precedence over config/env for the base URL. Resolved
     * values are memoized per instance — a sync makes hundreds of requests and
     * Setting::get() is a DB query.
     */
    protected function rawBaseUrl(): string
    {
        return $this->resolvedBaseUrl ??= (string) (Setting::get('autoseo_base_url') ?: config('blog-cms.autoseo.base_url', ''));
    }

    protected function apiToken(): string
    {
        return $this->resolvedApiToken ??= (string) (Setting::get('autoseo_api_token') ?: config('blog-cms.autoseo.api_token', ''));
    }

    protected function siteId(): string
    {
        return $this->resolvedSiteId ??= (string) (Setting::get('autoseo_site_id') ?: config('blog-cms.autoseo.site_id', ''));
    }

    /** Request timeout in seconds — config only. */
    protected function timeout(): int
    {
        return (int) config('blog-cms.autoseo.timeout', 30);
    }
}
