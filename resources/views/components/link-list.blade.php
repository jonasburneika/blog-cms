{{--
    Renders a managed link list as a <ul>. Fetch by key or pass a list:
        <x-blog::link-list key="footer" />
        <x-blog::link-list :list="$footer" class="footer__links" />
    Inactive links and dead internal targets (a deleted post/category) are
    skipped; external links opened in a new tab get rel="noopener noreferrer".
--}}
@props([
    'key' => null,
    'list' => null,
])
@php
    $list = $list ?? ($key ? \BlogCms\Models\BlogLinkList::forKey($key) : null);
    $links = $list ? $list->activeLinks : collect();
@endphp
@if($links->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'blog-link-list']) }}>
        @foreach($links as $link)
            @php($href = $link->href())
            @continue($href === '#')
            <li class="blog-link-list__item">
                <a href="{{ $href }}" class="blog-link-list__link"
                   @if($link->target !== '_self') target="{{ $link->target }}" @endif
                   @if($link->relAttribute()) rel="{{ $link->relAttribute() }}" @endif>{{ $link->label() }}</a>
            </li>
        @endforeach
    </ul>
@endif
