<?php

namespace BlogCms\Services;

/**
 * Result of generating a table of contents for one piece of HTML content.
 */
final class TocResult
{
    public function __construct(
        /** @var array<int, array{level:int,title:string,anchor:string,children?:array}> */
        public readonly array $items,
        /** Source HTML with unique heading `id`s injected. */
        public readonly string $html,
        /** sha256 of the source HTML (change detection). */
        public readonly string $hash,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
