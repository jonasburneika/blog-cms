<?php

namespace BlogCms\Services;

use BlogCms\Support\ImageFormats;
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
        $variants = $this->indexVariants($model, $collection);
        $changed = false;

        // The remote-fetch/base64 ingestion is toggleable; the lazy-loading pass
        // below always runs so images are deferred regardless of that setting.
        $ingestEnabled = (bool) config('blog-cms.media.ingest_content_images', true);

        foreach ((new DOMXPath($doc))->query('//img') as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }

            $src = trim($img->getAttribute('src'));
            $media = $src !== '' ? ($byUrl[$src] ?? null) : null;

            // 1) Ingest remote/base64 images into MediaLibrary and rewrite src.
            //    Skip empty srcs and images already stored locally for this model.
            if (! $media && $ingestEnabled && $src !== '') {
                // Previously ingested from this exact source → reuse, don't refetch.
                $media = $bySource[$src] ?? $this->store($model, $collection, $src);
                if ($media) {
                    $bySource[$src] = $media;
                    $byUrl[$media->getUrl()] = $media;
                    $img->setAttribute('src', $media->getUrl());
                    $changed = true;
                }
            }

            // 2) For any stored content image (just-ingested or already-local),
            //    add responsive srcset + intrinsic width/height, then wrap in a
            //    <picture> with WebP/AVIF <source>s when those formats are enabled.
            if ($media) {
                if ($this->applyResponsive($img, $media)) {
                    $changed = true;
                }
                if ($this->wrapInPicture($doc, $img, $media, $variants)) {
                    $changed = true;
                }
            }

            // 3) Stamp lazy-loading on EVERY image — ingested, already-local, or a
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

    /**
     * Responsive images for a stored content <img>: a width-bucket srcset (only
     * buckets no wider than the original — never upscaled) plus a sizes hint, and
     * width/height attributes. The admin's chosen display size wins; if none was
     * set, the image is capped at max_width keeping its natural aspect ratio.
     *
     * When the original is wider than max_width, `src` is pointed at the largest
     * bucket so the huge upload is never served (only offered via srcset for
     * pre-srcset browsers otherwise). Bucket URLs are indexed on load, so the
     * rewritten `src`/`srcset` are recognised on re-save and never re-ingested.
     *
     * Only sets attributes that are absent, and returns whether anything changed.
     */
    protected function applyResponsive(DOMElement $img, mixed $media): bool
    {
        try {
            $size = @getimagesize($media->getPath());
        } catch (Throwable) {
            $size = false;
        }
        if (! $size || (int) $size[0] < 1) {
            return false;
        }

        [$originalWidth, $originalHeight] = [(int) $size[0], (int) $size[1]];
        $maxWidth = (int) config('blog-cms.media.content_image.max_width', 1600);
        $ceiling = min($originalWidth, $maxWidth);
        $capped = $originalWidth > $maxWidth;

        $changed = false;

        // srcset from the buckets that fit within the original width.
        if (! $img->hasAttribute('srcset')) {
            $candidates = [];
            $largestBucketUrl = null;
            foreach (config('blog-cms.media.content_image.srcset_widths', []) as $width) {
                $width = (int) $width;
                if ($width < 1 || $width > $maxWidth || $width > $originalWidth) {
                    continue;
                }
                $url = $media->getUrl('content-'.$width);
                $candidates[$width] = $url.' '.$width.'w';
                $largestBucketUrl = $url; // widths iterate ascending → last is largest
            }

            // Include the original only when it isn't over the cap (else it's the
            // huge file we want to avoid serving).
            if (! $capped) {
                $candidates[$originalWidth] ??= $media->getUrl().' '.$originalWidth.'w';
            }

            // For a capped image, serve the largest bucket as src, not the original.
            if ($capped && $largestBucketUrl) {
                $img->setAttribute('src', $largestBucketUrl);
                $changed = true;
            }

            if (count($candidates) > 1) {
                ksort($candidates);
                $img->setAttribute('srcset', implode(', ', $candidates));
                if (! $img->hasAttribute('sizes')) {
                    $img->setAttribute('sizes', (string) config('blog-cms.media.content_image.sizes', '100vw'));
                }
                $changed = true;
            }
        }

        // width/height: honour the admin's set size (aspect ratio and all); else
        // cap at max_width using the image's natural aspect ratio.
        if (! $img->hasAttribute('width') || ! $img->hasAttribute('height')) {
            [$adminWidth, $adminHeight] = $this->authorSize($img);

            if ($adminWidth && $adminHeight) {
                $targetWidth = min($adminWidth, $ceiling);
                $targetHeight = (int) round($targetWidth * ($adminHeight / $adminWidth));
            } else {
                $targetWidth = $adminWidth ? min($adminWidth, $ceiling) : $ceiling;
                $targetHeight = (int) round($targetWidth * ($originalHeight / $originalWidth));
            }

            $img->setAttribute('width', (string) $targetWidth);
            $img->setAttribute('height', (string) $targetHeight);
            $changed = true;
        }

        return $changed;
    }

    /**
     * Wrap a stored content <img> in a <picture> with a <source> per enabled
     * modern format (AVIF/WebP, preference order), each a width-bucket srcset
     * built from the ShortPixel-converted variant media. The original <img> stays
     * as the last child (fallback). Idempotent: skipped when formats are off, the
     * image is already inside a <picture>, or the variants don't exist yet (they
     * are produced asynchronously by the ShortPixel job, which re-runs ingestion
     * once they're stored).
     *
     * @param  array<string,array<int,mixed>>  $variants  [format => [sourceMediaId => variantMedia]]
     */
    protected function wrapInPicture(DOMDocument $doc, DOMElement $img, mixed $media, array $variants): bool
    {
        if (! ImageFormats::enabled() || ! $variants) {
            return false;
        }

        $parent = $img->parentNode;
        if (! $parent || strtolower($parent->nodeName) === 'picture') {
            return false; // no parent to replace within, or already wrapped
        }

        try {
            $size = @getimagesize($media->getPath());
        } catch (Throwable) {
            $size = false;
        }
        if (! $size || (int) $size[0] < 1) {
            return false;
        }

        $originalWidth = (int) $size[0];
        $maxWidth = (int) config('blog-cms.media.content_image.max_width', 1600);
        $sizes = (string) config('blog-cms.media.content_image.sizes', '100vw');

        $sources = [];
        foreach (ImageFormats::enabled() as $format) {
            $variant = $variants[$format][$media->getKey()] ?? null;
            if (! $variant) {
                continue; // ShortPixel hasn't produced this format yet
            }

            $entries = [];
            foreach (config('blog-cms.media.content_image.srcset_widths', []) as $width) {
                $width = (int) $width;
                if ($width < 1 || $width > $maxWidth || $width > $originalWidth) {
                    continue;
                }
                $entries[] = $variant->getUrl('content-'.$width).' '.$width.'w';
            }
            if (! $entries) {
                continue;
            }

            $source = $doc->createElement('source');
            $source->setAttribute('type', 'image/'.$format);
            $source->setAttribute('srcset', implode(', ', $entries));
            $source->setAttribute('sizes', $sizes);
            $sources[] = $source;
        }

        if (! $sources) {
            return false;
        }

        $picture = $doc->createElement('picture');
        $parent->replaceChild($picture, $img);
        foreach ($sources as $source) {
            $picture->appendChild($source);
        }
        $picture->appendChild($img); // fallback comes last

        return true;
    }

    /**
     * Index the ShortPixel-converted format variants by source media id.
     *
     * @return array<string,array<int,mixed>>  [format => [sourceMediaId => variantMedia]]
     */
    protected function indexVariants(HasMedia $model, string $collection): array
    {
        $variants = [];

        foreach (ImageFormats::enabled() as $format) {
            foreach ($model->getMedia($collection.'-'.$format) as $variant) {
                $sourceId = $variant->getCustomProperty('variant_of');
                if ($sourceId) {
                    $variants[$format][(int) $sourceId] = $variant;
                }
            }
        }

        return $variants;
    }

    /**
     * The display size the admin set on the image, if any — from width/height
     * attributes or an inline `style="width:…px;height:…px"`. Returns [w, h] with
     * null for either dimension that isn't an explicit pixel value.
     *
     * @return array{0: ?int, 1: ?int}
     */
    protected function authorSize(DOMElement $img): array
    {
        $fromAttr = fn (string $name) => ctype_digit($v = trim($img->getAttribute($name))) ? (int) $v : null;

        $width = $fromAttr('width');
        $height = $fromAttr('height');

        if (($width === null || $height === null) && $img->hasAttribute('style')) {
            $style = $img->getAttribute('style');
            if ($width === null && preg_match('/(?:^|[;\s])width\s*:\s*(\d+)\s*px/i', $style, $m)) {
                $width = (int) $m[1];
            }
            if ($height === null && preg_match('/(?:^|[;\s])height\s*:\s*(\d+)\s*px/i', $style, $m)) {
                $height = (int) $m[1];
            }
        }

        return [$width, $height];
    }

    /** @return array{0: array<string,mixed>, 1: array<string,mixed>} [byUrl => media, bySourceUrl => media] */
    protected function indexExistingMedia(HasMedia $model, string $collection): array
    {
        $byUrl = [];
        $bySource = [];

        $bucketWidths = config('blog-cms.media.content_image.srcset_widths', []);

        foreach ($model->getMedia($collection) as $media) {
            $byUrl[$media->getUrl()] = $media;

            // Index the responsive-bucket URLs too, so a src/srcset we previously
            // rewrote to a "content-{w}" conversion is recognised (not re-ingested).
            foreach ($bucketWidths as $width) {
                $byUrl[$media->getUrl('content-'.(int) $width)] = $media;
            }

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
