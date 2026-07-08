{{--
    Post share buttons that tag the shared URL with UTM parameters. Theme-neutral
    (style the .blog-share* classes); the small JS is inlined once per page.

        <x-blog::share-buttons :post="$post" />
        <x-blog::share-buttons :url="$url" title="…" campaign="blog-post"
                               :networks="['twitter','facebook','linkedin','copy']" />

    Networks: twitter, facebook, linkedin, whatsapp, reddit, email, copy.
--}}
@props([
    'post' => null,
    'url' => null,
    'title' => null,
    'campaign' => null,
    'networks' => null,
])
@php
    $url = $url ?? ($post ? \BlogCms\Support\PageUrl::urlFor($post) : url()->current());
    $title = $title ?? ($post?->title ?: \BlogCms\Models\Setting::get('site_name', config('app.name')));
    $campaign = $campaign ?? config('blog-cms.sharing.campaign', 'share');
    $networks = $networks ?? config('blog-cms.sharing.networks', ['twitter', 'facebook', 'copy']);
    $labels = config('blog-cms.sharing.labels', []);
@endphp
<div {{ $attributes->merge(['class' => 'blog-share']) }}
     data-share="{{ $campaign }}" data-share-url="{{ $url }}" data-share-title="{{ $title }}">
    <span class="blog-share__label">{{ config('blog-cms.sharing.label', 'Share:') }}</span>
    @foreach($networks as $network)
        <button type="button" class="blog-share__btn blog-share__btn--{{ $network }}" data-share-method="{{ $network }}">
            {{ $labels[$network] ?? \Illuminate\Support\Str::title($network) }}
        </button>
    @endforeach
</div>
@once
    @include('blog::partials.share-script')
@endonce
