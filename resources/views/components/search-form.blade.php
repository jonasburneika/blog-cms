{{--
    Site search bar. Usage: <x-blog::search-form /> — or override the target:
    <x-blog::search-form action="/" placeholder="Search articles…" />.
    Posts to the configured search route (or "/" if the app owns its own home
    search) using the configured query parameter, preserving the current term.
--}}
@props([
    'action' => null,
    'placeholder' => 'Search…',
    'value' => null,
])
@php
    $param = config('blog-cms.search.query_param', 'q');
    $route = config('blog-cms.search.route');
    $action = $action ?? ($route ? url($route) : url('/'));
    $value = $value ?? request()->query($param, '');
@endphp
<form role="search" method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'blog-search-form']) }}>
    <label for="blog-search-input" class="blog-search-form__label sr-only">Search</label>
    <input type="search" id="blog-search-input" name="{{ $param }}" value="{{ $value }}"
           placeholder="{{ $placeholder }}" class="blog-search-form__input" autocomplete="off">
    <button type="submit" class="blog-search-form__button">Search</button>
</form>
