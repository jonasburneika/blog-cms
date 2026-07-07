{{--
    Example 404 page. It extends your app layout (config blog-cms.frontend.layout)
    so the header/footer — and therefore the site navigation — are preserved; the
    visitor is never stranded. It also offers a search bar and the top tags so
    they can find their way to real content.

    To use it, publish it into your app's error views:
        php artisan vendor:publish --tag=blog-frontend
    (Laravel resolves 404s from resources/views/errors/404.blade.php.)
--}}
@extends(config('blog-cms.frontend.layout') ?: 'blog::layouts.public')

@section('title', 'Page not found')

@section(config('blog-cms.frontend.section', 'content'))
    <section class="blog-404">
        <h1 class="blog-404__heading">404 — Page not found</h1>
        <p class="blog-404__text">
            The page you were looking for doesn’t exist or has moved.
            Try searching, or head back to the <a href="{{ url('/') }}">home page</a>.
        </p>

        <x-blog::search-form placeholder="Search for a topic…" class="blog-404__search" />

        <div class="blog-404__tags">
            <x-blog::top-tags title="Popular topics" />
        </div>
    </section>
@endsection
