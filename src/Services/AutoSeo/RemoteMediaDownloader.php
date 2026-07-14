<?php

namespace BlogCms\Services\AutoSeo;

use BlogCms\Models\AutoSeoMapping;
use BlogCms\Support\ImageUrlGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Downloads a remote AutoSEO media asset and attaches it to a model's Spatie
 * MediaLibrary collection, idempotently.
 *
 * Idempotency is keyed on the asset's checksum stored as the `autoseo_checksum`
 * custom property: if the collection already holds media with the same checksum,
 * it is returned untouched (no re-download). Every attachment is recorded as an
 * AutoSeoMapping of type media so the same asset maps to the same local Media.
 *
 * A media failure is never fatal to the surrounding import: any error is logged
 * and null is returned.
 */
class RemoteMediaDownloader
{
    public function __construct(protected AutoSeoClient $client) {}

    /**
     * Fetch remote media #$remoteMediaId and attach it to $model's $collection.
     *
     * Returns the (existing or newly created) Media model, or null when media
     * downloading is disabled, in dry-run, or on any failure.
     */
    public function attach(HasMedia $model, string $collection, int $remoteMediaId, bool $dryRun = false): ?Media
    {
        try {
            $asset = $this->client->mediaAsset($remoteMediaId);

            $checksum = isset($asset['checksum']) ? (string) $asset['checksum'] : null;

            // Idempotency: an already-attached asset with the same checksum is
            // returned as-is, regardless of the download toggle or dry-run.
            if ($checksum !== null && $checksum !== '') {
                foreach ($model->getMedia($collection) as $existing) {
                    if ((string) $existing->getCustomProperty('autoseo_checksum') === $checksum) {
                        return $existing;
                    }
                }
            }

            // Nothing to (re)download in dry-run or when media sync is disabled.
            if ($dryRun || ! (bool) config('blog-cms.autoseo.download_media', true)) {
                return null;
            }

            $url = $this->client->absoluteUrl($asset['url'] ?? null);
            if (! $url) {
                Log::warning('[blog-cms][autoseo] media has no resolvable URL', [
                    'remote_media_id' => $remoteMediaId,
                    'collection' => $collection,
                ]);

                return null;
            }

            // The configured AutoSEO host is trusted; anything else the API
            // points us at (CDN, object store) must pass the SSRF guard.
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if ($host !== strtolower($this->client->baseHost()) && ($reason = ImageUrlGuard::blockReason($url)) !== null) {
                Log::warning('[blog-cms][autoseo] media URL blocked', [
                    'remote_media_id' => $remoteMediaId,
                    'url' => $url,
                    'reason' => $reason,
                ]);

                return null;
            }

            $bytes = $this->client->downloadFile($url);
            if ($bytes === '') {
                return null;
            }

            $fileName = $this->fileName($asset, $remoteMediaId);

            $tmp = tempnam(sys_get_temp_dir(), 'autoseo');
            file_put_contents($tmp, $bytes);

            try {
                $media = $model->addMedia($tmp)
                    ->usingFileName($fileName)
                    ->withCustomProperties([
                        'autoseo_media_id' => $remoteMediaId,
                        'autoseo_checksum' => $checksum,
                        'alt_text' => $asset['alt_text'] ?? null,
                        'title' => $asset['title_attr'] ?? null,
                        'caption' => $asset['caption'] ?? null,
                    ])
                    ->toMediaCollection($collection);
            } finally {
                if (is_file($tmp)) {
                    @unlink($tmp); // addMedia moves it; safety net
                }
            }

            AutoSeoMapping::record(AutoSeoMapping::TYPE_MEDIA, $remoteMediaId, $media);

            return $media;
        } catch (Throwable $e) {
            Log::warning('[blog-cms][autoseo] media download failed', [
                'remote_media_id' => $remoteMediaId,
                'collection' => $collection,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * A sane on-disk file name: the remote original filename when present,
     * otherwise a random name carrying the original extension.
     */
    protected function fileName(array $asset, int $remoteMediaId): string
    {
        $original = trim((string) ($asset['original_filename'] ?? ''));

        if ($original !== '') {
            $base = pathinfo($original, PATHINFO_FILENAME);
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $base = Str::slug($base) ?: 'autoseo-'.$remoteMediaId;

            return $ext !== '' ? $base.'.'.strtolower($ext) : $base;
        }

        $ext = $this->extensionFromMime((string) ($asset['mime_type'] ?? ''));

        return 'autoseo-'.$remoteMediaId.$ext;
    }

    protected function extensionFromMime(string $mime): string
    {
        return match (strtolower(trim(explode(';', $mime)[0]))) {
            'image/jpeg', 'image/jpg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/avif' => '.avif',
            'image/svg+xml' => '.svg',
            default => '.bin',
        };
    }
}
