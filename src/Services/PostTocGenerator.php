<?php

namespace BlogCms\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Builds a table of contents from blog-post HTML.
 *
 * Server-side DOM parsing (not regex) so it survives attributes, nested inline
 * tags, malformed markup and UTF-8/Lithuanian text. It extracts H2 (top level)
 * and H3 (nested) headings, gives each a unique slug `id`, injects those ids
 * back into the HTML, and returns the nested nav structure + a content hash.
 */
class PostTocGenerator
{
    /** Ancestor tags whose headings are structural chrome, not article sections. */
    private const SKIP_TAGS = ['nav', 'aside', 'footer'];

    /** Ancestor class fragments that mark non-article blocks. */
    private const SKIP_CLASS_HINTS = ['related', 'author', 'widget', 'footnote', 'toc', 'reusable'];

    public function generate(?string $html): TocResult
    {
        $html = (string) $html;
        $hash = hash('sha256', $html);

        if (trim($html) === '') {
            return new TocResult([], $html, $hash);
        }

        $doc = $this->loadHtml($html);
        if (! $doc) {
            return new TocResult([], $html, $hash);
        }

        $xpath = new DOMXPath($doc);
        $headings = $xpath->query('//h2 | //h3'); // document order

        $items = [];
        $used = [];
        $topIndex = null;

        foreach ($headings as $heading) {
            if (! $heading instanceof DOMElement || $this->shouldSkip($heading)) {
                continue;
            }

            $title = $this->textOf($heading);
            if ($title === '') {
                continue;
            }

            $anchor = $this->uniqueAnchor($heading, $title, $used);
            $heading->setAttribute('id', $anchor);

            $level = (int) substr($heading->tagName, 1);

            if ($level === 2) {
                $items[] = ['level' => 2, 'title' => $title, 'anchor' => $anchor, 'children' => []];
                $topIndex = count($items) - 1;
            } elseif ($topIndex !== null) {
                $items[$topIndex]['children'][] = ['level' => 3, 'title' => $title, 'anchor' => $anchor];
            } else {
                // An H3 before any H2 — promote it to a top-level entry.
                $items[] = ['level' => 3, 'title' => $title, 'anchor' => $anchor, 'children' => []];
            }
        }

        return new TocResult($items, $this->saveHtml($doc), $hash);
    }

    // ─── DOM helpers ─────────────────────────────────────────────

    private function loadHtml(string $html): ?DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        // The XML PI forces UTF-8; NOIMPLIED/NODEFDTD keep it a bare fragment
        // (no <html>/<body>/doctype wrapper).
        $ok = $doc->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $ok) {
            return null;
        }

        // Drop the XML processing instruction we injected.
        foreach (iterator_to_array($doc->childNodes) as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                $doc->removeChild($node);
            }
        }

        return $doc;
    }

    private function saveHtml(DOMDocument $doc): string
    {
        $html = $doc->saveHTML();

        return $html === false ? '' : trim($html);
    }

    /** Collapsed plain text of a heading (inline tags stripped, entities decoded). */
    private function textOf(DOMElement $heading): string
    {
        return trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '');
    }

    /**
     * Prefer a valid existing id, otherwise slugify the title; always make it
     * unique within the article.
     */
    private function uniqueAnchor(DOMElement $heading, string $title, array &$used): string
    {
        $existing = trim($heading->getAttribute('id'));
        $base = $existing !== '' ? $existing : (Str::slug($title) ?: 'section');

        $anchor = $base;
        $suffix = 1;
        while (isset($used[$anchor])) {
            $suffix++;
            $anchor = $base.'-'.$suffix;
        }

        $used[$anchor] = true;

        return $anchor;
    }

    /** Skip headings flagged directly, or inside nav/aside/footer / non-article blocks. */
    private function shouldSkip(DOMElement $heading): bool
    {
        if ($heading->hasAttribute('data-toc-skip')) {
            return true;
        }

        for ($node = $heading->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            if (in_array(strtolower($node->tagName), self::SKIP_TAGS, true)) {
                return true;
            }

            if ($node->hasAttribute('data-toc-skip')) {
                return true;
            }

            $class = strtolower($node->getAttribute('class'));
            if ($class !== '') {
                foreach (self::SKIP_CLASS_HINTS as $hint) {
                    if (str_contains($class, $hint)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
