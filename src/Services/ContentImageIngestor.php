<?php

namespace BlogCms\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
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
 * so repeated API saves never duplicate files. Also stamps loading="lazy" and
 * width/height to help layout stability.
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

        foreach ((new DOMXPath($doc))->query('//img') as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }

            $src = trim($img->getAttribute('src'));
            if ($src === '' || isset($byUrl[$src])) {
                continue; // empty, or already a stored media URL for this model
            }

            // Previously ingested from this exact source → reuse, don't refetch.
            $media = $bySource[$src] ?? $this->store($model, $collection, $src);
            if (! $media) {
                continue;
            }

            $bySource[$src] = $media;
            $byUrl[$media->getUrl()] = true;
            $this->applyMedia($img, $media);
            $changed = true;
        }

        return $changed ? $this->save($doc) : $html;
    }

    // ─── Storage ─────────────────────────────────────────────────

    protected function store(HasMedia $model, string $collection, string $src): mixed
    {
        try {
            if (Str::startsWith($src, 'data:image')) {
                $adder = $model->addMediaFromBase64($src)->usingFileName(Str::random(16).$this->extensionFromDataUri($src));
            } elseif (Str::startsWith($src, ['http://', 'https://'])) {
                $adder = $model->addMediaFromUrl($src);
            } else {
                return null; // relative/unknown path — leave the markup untouched
            }

            return $adder->withCustomProperties(['source_url' => $src])->toMediaCollection($collection);
        } catch (Throwable) {
            return null; // a single bad image must never break the save
        }
    }

    protected function applyMedia(DOMElement $img, mixed $media): void
    {
        $img->setAttribute('src', $media->getUrl());

        if (! $img->hasAttribute('loading')) {
            $img->setAttribute('loading', 'lazy');
        }

        if (! $img->hasAttribute('width') || ! $img->hasAttribute('height')) {
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
