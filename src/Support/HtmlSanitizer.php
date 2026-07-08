<?php

namespace BlogCms\Support;

use DOMComment;
use DOMElement;
use DOMDocument;
use DOMNode;

/**
 * Allowlist HTML sanitizer for untrusted (guest-submitted) content, so it is
 * safe to render through a raw `{!! !!}` sink. Tags not on the allowlist are
 * unwrapped (their text is kept); `<script>`/`<style>` are dropped entirely;
 * every attribute except a small per-tag safe set is removed; and
 * javascript:/vbscript:/data:text/html URLs are stripped from href/src.
 */
class HtmlSanitizer
{
    /** Attributes kept per tag (everything else is removed). */
    protected const TAG_ATTRS = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    public static function clean(?string $html, ?array $allowedTags = null): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $allowed = array_map('strtolower', $allowedTags
            ?? (array) config('blog-cms.guest_blogging.allowed_html', []));

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // Wrap in a known root so we can serialize just the inner markup.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = null;
        foreach ($doc->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $root = $node;
                break;
            }
        }
        if (! $root) {
            return '';
        }

        static::sanitizeChildren($root, $allowed);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    protected static function sanitizeChildren(DOMNode $node, array $allowed): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $child->parentNode->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue; // text nodes are kept as-is
            }

            $tag = strtolower($child->nodeName);

            if ($tag === 'script' || $tag === 'style') {
                $child->parentNode->removeChild($child); // drop node + contents
                continue;
            }

            // Clean descendants first, then decide this node's fate.
            static::sanitizeChildren($child, $allowed);

            if (! in_array($tag, $allowed, true)) {
                static::unwrap($child); // keep the text, drop the tag

                continue;
            }

            static::filterAttributes($child, $tag);
        }
    }

    /** Replace an element with its children (kept in place). */
    protected static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    protected static function filterAttributes(DOMElement $element, string $tag): void
    {
        $allowedAttrs = static::TAG_ATTRS[$tag] ?? [];

        foreach (iterator_to_array($element->attributes) as $attr) {
            $name = strtolower($attr->nodeName);

            if (! in_array($name, $allowedAttrs, true)) {
                $element->removeAttribute($attr->nodeName);

                continue;
            }

            if (($name === 'href' || $name === 'src') && static::isDangerousUrl($attr->nodeValue)) {
                $element->removeAttribute($attr->nodeName);
            }
        }

        // Harden external links opened in a new tab.
        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    protected static function isDangerousUrl(?string $url): bool
    {
        // Collapse whitespace/control chars used to smuggle "java\nscript:".
        $u = strtolower(preg_replace('/[\s\x00-\x1f]+/', '', (string) $url) ?? '');

        return str_starts_with($u, 'javascript:')
            || str_starts_with($u, 'vbscript:')
            || str_starts_with($u, 'data:text/html');
    }
}
