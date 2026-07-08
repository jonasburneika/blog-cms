<?php

namespace BlogCms\Services;

use BlogCms\Support\ImageUrlGuard;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Throwable;

/**
 * Pulls inline `<img>` images out of post content HTML and into the model's
 * Spatie MediaLibrary collection, rewriting each `src` to the stored media URL.
 *
 * Handles remote URLs (downloaded) and base64 data-URIs (decoded). Idempotent:
 * an image already stored locally is left alone, and re-sending the same source
 * URL reuses the existing media (matched via the `source_url` custom property),
 * so repeated API saves never duplicate files.
 *
 * Regardless of ingestion, every content `<img>` — including the fallback image
 * inside a `<picture>` — is stamped with loading="lazy" and decoding="async"
 * (unless the author set them), and ingested images also get intrinsic
 * width/height, to reduce layout shift and defer off-screen loads.
 */
class ContentImageIngestor
{
    public function ingest(HasMedia $model, ?string $html, ?string $collection = null): string
    {
        $html = (string) $html;
        $collection ??= config('blog-cms.media.content_collection', 'content');

        if (trim($html) === '' || ! str_contains($html, '<img')) {
            return $html;
        }

        $doc = $this->load($html);
        if (! $doc) {
            return $html;
        }

        [$byUrl, $bySource] = $this->indexExistingMedia($model, $collection);
        $changed = false;

        // The remote-fetch/base64 ingestion is toggleable; the lazy-loading pass
        // below always runs so images are deferred regardless of that setting.
        $ingestEnabled = (bool) config('blog-cms.media.ingest_content_images', true);

        foreach ((new DOMXPath($doc))->query('//img') as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }

            $src = trim($img->getAttribute('src'));

            // 1) Ingest remote/base64 images into MediaLibrary and rewrite src.
            //    Skip empty srcs and images already stored locally for this model.
            if ($ingestEnabled && $src !== '' && ! isset($byUrl[$src])) {
                // Previously ingested from this exact source → reuse, don't refetch.
                $media = $bySource[$src] ?? $this->store($model, $collection, $src);
                if ($media) {
                    $bySource[$src] = $media;
                    $byUrl[$media->getUrl()] = true;
                    $img->setAttribute('src', $media->getUrl());
                    $this->applyDimensions($img, $media);
                    $changed = true;
                }
            }

            // 2) Stamp lazy-loading on EVERY image — ingested, already-local, or a
            //    relative/app path — including the <img> fallback inside <picture>.
            if ($this->lazyLoad($img)) {
                $changed = true;
            }
        }

        return $changed ? $this->save($doc) : $html;
    }

    // ─── Storage ─────────────────────────────────────────────────

    protected function store(HasMedia $model, string $collection, string $src): mixed
    {
        try {
            if (Str::startsWith($src, 'data:image')) {
                return $this->storeBase64($model, $collection, $src);
            }

            if (Str::startsWith($src, ['http://', 'https://'])) {
                return $this->storeRemote($model, $collection, $src);
            }

            return null; // relative/unknown path — leave the markup untouched
        } catch (Throwable $e) {
            Log::warning('[blog-cms] content image ingest failed', ['url' => $src, 'error' => $e->getMessage()]);

            return null; // a single bad image must never break the save
        }
    }

    protected function storeBase64(HasMedia $model, string $collection, string $src): mixed
    {
        if (strlen($src) > (int) ($this->maxBytes() * 1.4)) {
            Log::warning('[blog-cms] content image rejected: base64 too large');

            return null;
        }

        return $model->addMediaFromBase64($src)
            ->usingFileName(Str::random(16).$this->extensionFromDataUri($src))
            ->withCustomProperties(['source_url' => $src])
            ->toMediaCollection($collection);
    }

    /**
     * Fetch a remote image behind the SSRF guard: only public hosts, no
     * redirects (a 3xx could bounce to a blocked host), size + content-type
     * bounded. Downloads to a temp file, then hands it to MediaLibrary.
     */
    protected function storeRemote(HasMedia $model, string $collection, string $src): mixed
    {
        if ($reason = ImageUrlGuard::blockReason($src)) {
            Log::warning('[blog-cms] content image fetch blocked (SSRF guard)', ['url' => $src, 'reason' => $reason]);

            return null;
        }

        $response = Http::withOptions(['allow_redirects' => false])
            ->timeout((int) config('blog-cms.media.fetch_timeout', 15))
            ->accept('image/*')
            ->get($src);

        if (! $response->ok()) { // rejects 3xx redirects and errors
            Log::warning('[blog-cms] content image fetch rejected', ['url' => $src, 'status' => $response->status()]);

            return null;
        }

        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $body = $response->body();
        if (! str_starts_with($type, 'image/') || $body === '' || strlen($body) > $this->maxBytes()) {
            Log::warning('[blog-cms] content image rejected: bad type or size', ['url' => $src, 'type' => $type]);

            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'bcimg');
        file_put_contents($tmp, $body);

        try {
            return $model->addMedia($tmp)
                ->usingFileName(Str::random(16).$this->extensionFromMime($type))
                ->withCustomProperties(['source_url' => $src])
                ->toMediaCollection($collection);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp); // addMedia moves it; this is a safety net
            }
        }
    }

    protected function maxBytes(): int
    {
        return (int) config('blog-cms.media.max_image_bytes', 15 * 1024 * 1024);
    }

    protected function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/avif' => '.avif',
            'image/svg+xml' => '.svg',
            default => '.img',
        };
    }

    /**
     * Lazy-loading hints applied to every content image (browser-native lazy
     * load + async decode), only when the author hasn't set them explicitly.
     * Returns whether anything changed so the caller knows to re-save.
     */
    protected function lazyLoad(DOMElement $img): bool
    {
        $changed = false;

        if (! $img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
            $changed = true;
        }

        if (! $img->hasAttribute('decoding')) {
            $img->setAttribute('decoding', 'async');
            $changed = true;
        }

        return $changed;
    }

    /** Best-effort intrinsic width/height (from the stored file) to cut layout shift. */
    protected function applyDimensions(DOMElement $img, mixed $media): void
    {
        if ($img->hasAttribute('width') && $img->hasAttribute('height')) {
            return;
        }

        try {
            $size = @getimagesize($media->getPath());
            if ($size) {
                $img->setAttribute('width', (string) $size[0]);
                $img->setAttribute('height', (string) $size[1]);
            }
        } catch (Throwable) {
            // dimensions are best-effort
        }
    }

    /** @return array{0: array<string,bool>, 1: array<string,mixed>} [byUrl, bySourceUrl] */
    protected function indexExistingMedia(HasMedia $model, string $collection): array
    {
        $byUrl = [];
        $bySource = [];

        foreach ($model->getMedia($collection) as $media) {
            $byUrl[$media->getUrl()] = true;
            if ($source = $media->getCustomProperty('source_url')) {
                $bySource[$source] = $media;
            }
        }

        return [$byUrl, $bySource];
    }

    protected function extensionFromDataUri(string $src): string
    {
        if (preg_match('#^data:image/([a-zA-Z0-9.+-]+);#', $src, $m)) {
            return '.'.strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        }

        return '.png';
    }

    // ─── DOM ─────────────────────────────────────────────────────

    protected function load(string $html): ?DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $ok = $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $ok) {
            return null;
        }

        foreach (iterator_to_array($doc->childNodes) as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $doc->removeChild($node);
            }
        }

        return $doc;
    }

    protected function save(DOMDocument $doc): string
    {
        $html = $doc->saveHTML();

        return $html === false ? '' : trim($html);
    }
}
