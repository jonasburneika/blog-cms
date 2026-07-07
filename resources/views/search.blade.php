@extends(config('blog-cms.frontend.layout') ?: 'blog::layouts.public')
@use('BlogCms\Support\PageUrl')

@section('title', $term !== '' ? 'Search: ' . e($term) : 'Search')

@section(config('blog-cms.frontend.section', 'content'))
    <section class="blog-search">
        <h1 class="blog-search__heading">Search</h1>

        <x-blog::search-form :value="$term" class="blog-search__form" />

        @if($term === '')
            <p class="blog-search__hint">Type a word or phrase to search posts, categories and topics.</p>
            <x-blog::top-tags :tags="$topTags" />
        @else
            <p class="blog-search__summary">
                Results for &ldquo;<strong>{{ $term }}</strong>&rdquo;
            </p>

            {{-- Matching categories --}}
            @if($categories->isNotEmpty())
                <section class="blog-search__group">
                    <h2 class="blog-search__group-title">Categories</h2>
                    <ul class="blog-search__categories">
                        @foreach($categories as $category)
                            <li><a href="{{ PageUrl::urlFor($category) }}">{{ $category->title }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Matching tags (post topics) --}}
            @if($tags->isNotEmpty())
                <section class="blog-search__group">
                    <h2 class="blog-search__group-title">Tags</h2>
                    <x-blog::top-tags :tags="$tags" title="Matching tags" />
                </section>
            @endif

            {{-- Matching posts --}}
            <section class="blog-search__group">
                <h2 class="blog-search__group-title">Posts</h2>
                @forelse($posts as $post)
                    <article class="blog-search__result">
                        <h3 class="blog-search__result-title">
                            <a href="{{ PageUrl::urlFor($post) }}">{{ $post->title }}</a>
                        </h3>
                        @if($post->category)
                            <p class="blog-search__result-meta">{{ $post->category->title }}</p>
                        @endif
                        @if($post->excerpt)
                            <p class="blog-search__result-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 180) }}</p>
                        @endif
                    </article>
                @empty
                    <p class="blog-search__empty">No posts matched your search. Try another term or browse popular topics:</p>
                    <x-blog::top-tags :tags="$topTags" />
                @endforelse

                @if($posts && $posts->hasPages())
                    <div class="blog-search__pagination">{{ $posts->links() }}</div>
                @endif
            </section>
        @endif
    </section>
@endsection
