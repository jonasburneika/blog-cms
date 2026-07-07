{{--
    Minimal standalone layout used by the package's search + 404 pages when the
    app has NOT set config('blog-cms.frontend.layout'). It still gives visitors
    navigation (home link + search) so no page is a dead end. Point the config at
    your own layout to inherit your real header/footer instead.
--}}
@php
    $siteName = \BlogCms\Models\Setting::get('site_name', config('app.name'));
    $section = config('blog-cms.frontend.section', 'content');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteName)</title>
    <x-blog::seo-meta />
</head>
<body class="blog-public">
    <header class="blog-public__header">
        <a href="{{ url('/') }}" class="blog-public__brand">{{ $siteName }}</a>
        <x-blog::search-form class="blog-public__search" />
    </header>

    <main class="blog-public__main">
        @yield($section)
    </main>

    <footer class="blog-public__footer">
        <a href="{{ url('/') }}">&larr; Back to home</a>
        <p>&copy; {{ now()->year }} {{ $siteName }}</p>
    </footer>
</body>
</html>
