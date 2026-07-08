{{--
    Site-wide social media links (managed in admin Settings → General). Renders
    a <ul> of external links; nothing when none are set. Style the .blog-social*
    classes in your theme.
        <x-blog::social-links class="footer__social" />
--}}
@props([
    'links' => null,
])
@php
    $links = $links ?? \BlogCms\Models\Setting::get('social_media_links', []);
    $links = array_values(array_filter((array) $links, fn ($l) => ! empty($l['url'])));
@endphp
@if($links)
    <ul {{ $attributes->merge(['class' => 'blog-social']) }}>
        @foreach($links as $link)
            <li class="blog-social__item">
                <a href="{{ $link['url'] }}" class="blog-social__link" target="_blank" rel="noopener noreferrer me">
                    {{ $link['label'] ?: parse_url($link['url'], PHP_URL_HOST) }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
