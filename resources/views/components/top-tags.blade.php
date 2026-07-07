{{--
    "Top tags" widget — the most-used tags (post topics) across published posts,
    each linking to a search for that tag. Self-fetches from SearchService when
    no :tags collection is passed, so it works standalone on a 404 page:
        <x-blog::top-tags />
        <x-blog::top-tags :tags="$topTags" title="Trending" :limit="5" />
--}}
@props([
    'tags' => null,
    'limit' => null,
    'title' => 'Popular topics',
])
@php
    $tags = $tags ?? app(\BlogCms\Services\SearchService::class)->topTags($limit);
    $param = config('blog-cms.search.query_param', 'q');
    $route = config('blog-cms.search.route');
    $base = $route ? url($route) : url('/');
@endphp
@if($tags && $tags->isNotEmpty())
    <nav {{ $attributes->merge(['class' => 'blog-top-tags']) }} aria-label="{{ $title }}">
        <p class="blog-top-tags__title">{{ $title }}</p>
        <ul class="blog-top-tags__list">
            @foreach($tags as $tag)
                <li class="blog-top-tags__item">
                    <a href="{{ $base }}?{{ $param }}={{ urlencode($tag->name) }}" class="blog-top-tags__link">
                        {{ $tag->name }}
                    </a>
                    <span class="blog-top-tags__count">{{ $tag->posts_count }}</span>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
