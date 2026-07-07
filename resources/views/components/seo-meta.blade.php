{{--
    <head> SEO tags: robots directive, canonical, Open Graph, and Twitter card.
    Drop into your layout's <head> on every public page:

        <x-blog::seo-meta
            title="Post title"
            description="…"
            image="https://…/cover.jpg"
            type="article" />

    All props are optional — they fall back to the settings table + config so the
    front page (and any page rendered without props) still gets a complete set of
    tags, with robots defaulting to "index, follow". The robots value and the
    pagination canonical are derived from the current request via the same
    BlogCms\Support\SeoMeta used by the X-Robots-Tag / Link response headers.

    Props:
      $title, $description, $image, $url  — per-page overrides
      $type        Open Graph type (default "website"; use "article" on posts)
      $siteName    og:site_name (default: site_name setting)
      $twitterCard (default from config blog-cms.social.twitter_card)
      $twitterSite (default from config blog-cms.social.twitter_site)
      $robots      override the robots directive (default: derived from request)
      $canonical   override the canonical URL (default: pagination primary, if any)
--}}
@use('BlogCms\Models\Setting')
@use('BlogCms\Support\SeoMeta')
@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
    'siteName' => null,
    'twitterCard' => null,
    'twitterSite' => null,
    'robots' => null,
    'canonical' => null,
])
@php
    $request = request();

    $siteName    = $siteName    ?? Setting::get('site_name', config('app.name'));
    $title       = $title       ?? Setting::get('homepage_meta_title', '') ?: $siteName;
    $description  = $description ?? (Setting::get('homepage_meta_description', '') ?: Setting::get('site_description', ''));
    $url         = $url         ?? $request->url();
    $image       = $image       ?? config('blog-cms.social.og_image');
    $twitterCard = $twitterCard ?? config('blog-cms.social.twitter_card', 'summary_large_image');
    $twitterSite = $twitterSite ?? config('blog-cms.social.twitter_site');
    $robots      = $robots      ?? SeoMeta::robots($request);
    $canonical   = $canonical   ?? SeoMeta::paginationCanonical($request);
@endphp
<meta name="robots" content="{{ $robots }}">
@if($canonical)
<link rel="canonical" href="{{ $canonical }}">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
@if($description)
<meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $url }}">
@if($siteName)
<meta property="og:site_name" content="{{ $siteName }}">
@endif
@if($image)
<meta property="og:image" content="{{ $image }}">
@endif

{{-- Twitter card --}}
<meta name="twitter:card" content="{{ $image ? $twitterCard : 'summary' }}">
@if($twitterSite)
<meta name="twitter:site" content="{{ $twitterSite }}">
@endif
<meta name="twitter:title" content="{{ $title }}">
@if($description)
<meta name="twitter:description" content="{{ $description }}">
@endif
@if($image)
<meta name="twitter:image" content="{{ $image }}">
@endif
