<?php

namespace BlogCms\Services;

use BlogCms\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the ShortPixel reducer API (https://shortpixel.com/api-docs).
 * Sends a public image URL for optimization and downloads the optimized bytes.
 * The queue job (OptimizeMediaWithShortPixel) orchestrates polling + write-back;
 * this class just does the HTTP.
 */
class ShortPixelOptimizer
{
    protected const ENDPOINT = 'https://api.shortpixel.com/v2/reducer.php';

    /** Success = optimized, Pending = still processing on ShortPixel's side. */
    public const STATUS_SUCCESS = 2;
    public const STATUS_PENDING = 1;

    /**
     * Settings (admin page) take precedence over config/env, so the API key and
     * toggle can be managed without touching .env.
     */
    public function enabled(): bool
    {
        $enabled = Setting::get('shortpixel_enabled', config('blog-cms.media.shortpixel.enabled', false));

        return (bool) $enabled && $this->apiKey() !== '';
    }

    public function apiKey(): string
    {
        return (string) (Setting::get('shortpixel_api_key') ?: config('blog-cms.media.shortpixel.api_key', ''));
    }

    public function compression(): string
    {
        return (string) (Setting::get('shortpixel_compression') ?: config('blog-cms.media.shortpixel.compression', 'lossy'));
    }

    /**
     * Ask ShortPixel to optimize a public image URL. Returns the first result
     * object from the reducer (with a `Status.Code`), or null on transport error.
     *
     * @return array<string,mixed>|null
     */
    public function reduce(string $publicUrl): ?array
    {
        $wait = (int) config('blog-cms.media.shortpixel.wait', 30);

        try {
            $response = Http::timeout($wait + 15)
                ->acceptJson()
                ->asJson()
                ->post(self::ENDPOINT, [
                    'key' => $this->apiKey(),
                    'lossy' => $this->compressionCode(),
                    'wait' => max(0, min(30, $wait)),
                    'convertto' => '',   // keep the original format (in-place replace)
                    'resize' => 0,       // sizing is handled by our srcset conversions
                    'refresh' => 0,
                    'urllist' => [$publicUrl],
                ]);
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] ShortPixel request failed', ['url' => $publicUrl, 'error' => $e->getMessage()]);

            return null;
        }

        $json = $response->json();

        // The reducer returns a list (one entry per URL) or, on a global error,
        // an object with a Status. Normalise to the first per-image result.
        if (is_array($json) && array_is_list($json)) {
            return $json[0] ?? null;
        }

        return is_array($json) ? $json : null;
    }

    /** The optimized-file URL for the configured compression, if the item succeeded. */
    public function optimizedUrl(array $item): ?string
    {
        $url = $this->compression() === 'lossless'
            ? ($item['LosslessURL'] ?? null)
            : ($item['LossyURL'] ?? null);

        // "NA" is ShortPixel's placeholder when a variant wasn't produced.
        return is_string($url) && $url !== '' && $url !== 'NA' ? $url : null;
    }

    /** Download optimized bytes from a ShortPixel result URL. */
    public function download(string $url): ?string
    {
        try {
            $response = Http::timeout((int) config('blog-cms.media.fetch_timeout', 15))->get($url);
        } catch (\Throwable $e) {
            Log::warning('[blog-cms] ShortPixel download failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        return $response->ok() ? $response->body() : null;
    }

    /** ShortPixel `lossy` code: 0 lossless, 1 lossy, 2 glossy. */
    protected function compressionCode(): int
    {
        return match ($this->compression()) {
            'lossless' => 0,
            'glossy' => 2,
            default => 1,
        };
    }
}
