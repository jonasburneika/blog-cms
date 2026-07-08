@php
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $lang = str_replace('_', '-', $locale);
@endphp
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title>{{ $title }}</title>
<link>{{ url('/') }}</link>
<description>{{ $description }}</description>
<language>{{ $lang }}</language>
<atom:link href="{{ url()->current() }}" rel="self" type="application/rss+xml"/>
@if($posts->isNotEmpty())
<lastBuildDate>{{ optional($posts->first()->published_at)->toRssString() }}</lastBuildDate>
@endif
@foreach($posts as $post)
@php
    $url = \BlogCms\Support\PageUrl::urlFor($post, $locale);
    $content = (string) $post->getTranslation('content', $locale, true);
    // Guard against a stray "]]>" breaking out of the CDATA section.
    $content = str_replace(']]>', ']]]]><![CDATA[>', $content);
@endphp
<item>
<title>{{ $post->getTranslation('title', $locale, true) }}</title>
<link>{{ $url }}</link>
<guid isPermaLink="true">{{ $url }}</guid>
<pubDate>{{ optional($post->published_at)->toRssString() }}</pubDate>
<description>{{ \Illuminate\Support\Str::limit(strip_tags((string) $post->getTranslation('excerpt', $locale, true)), 500) }}</description>
<content:encoded><![CDATA[{!! $content !!}]]></content:encoded>
@if($post->category)
<category>{{ $post->category->getTranslation('title', $locale, true) }}</category>
@endif
</item>
@endforeach
</channel>
</rss>
