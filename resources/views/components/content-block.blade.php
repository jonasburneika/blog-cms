{{--
    Renders a managed WYSIWYG content block by key (or pass a :block). Used for
    homepage copy around the featured posts:
        <x-blog::content-block key="homepage_before_featured" />
        ...featured posts...
        <x-blog::content-block key="homepage_after_featured" />
    Renders nothing when the block is missing, inactive, or empty. Content is
    admin-authored (trusted), so it's output as raw HTML.
--}}
@props([
    'key' => null,
    'block' => null,
    'headingTag' => 'h2',
])
@php
    $block = $block ?? ($key ? \BlogCms\Models\BlogContentBlock::forKey($key) : null);
    $html = $block ? $block->html() : '';
@endphp
@if($block && trim($html) !== '')
    <div {{ $attributes->merge(['class' => 'blog-content-block']) }}>
        @if($block->title() !== '')
            <{{ $headingTag }} class="blog-content-block__title">{{ $block->title() }}</{{ $headingTag }}>
        @endif
        <div class="blog-content-block__body">{!! $html !!}</div>
    </div>
@endif
