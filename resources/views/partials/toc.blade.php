{{--
    In-article table of contents.

    Usage from the app's post template:
        @include('blog::partials.toc', ['post' => $post])
        {!! $post->renderedContent() !!}   {{-- body HTML with heading ids --}}

    Params:
      $post    the blog post (uses HasTableOfContents)
      $title   heading label (default "Contents")
      $locale  force a locale (defaults to the current app locale)

    Renders nothing unless the TOC is enabled and has at least two entries.
--}}
@php
    $tocLocale = $locale ?? null;
    $tocTitle = $title ?? 'Contents';
@endphp
@if($post->hasTableOfContents($tocLocale))
    <nav class="blog-post-toc" aria-label="Article contents">
        <p class="blog-post-toc__title">{{ $tocTitle }}</p>
        <ol>
            @foreach($post->tableOfContents($tocLocale) as $item)
                <li>
                    <a href="#{{ $item['anchor'] }}">{{ $item['title'] }}</a>
                    @if(!empty($item['children']))
                        <ol>
                            @foreach($item['children'] as $child)
                                <li><a href="#{{ $child['anchor'] }}">{{ $child['title'] }}</a></li>
                            @endforeach
                        </ol>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
