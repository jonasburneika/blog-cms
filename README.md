# blog-cms

A reusable, **headless-ish blog CMS core** for Laravel 12. It's a deliberately *boring* blog: models, admin management, schema.org structured data, media optimization, DB-backed translations, guest blogging, multi-author, and cache/tracking settings — and **nothing site-specific**. No frontend theme, no calculators, no custom domain models. Those live in each consuming app.

Extracted so multiple sites (e.g. a brewery blog, a soil-science blog) share one maintained core and only build their own theme + domain on top.

## What's in core

- **Models** — `BlogPost`, `BlogCategory`, `BlogPostFaq`, `BlogPostHowToStep`, `BlogPostHook`, `HookTemplate`, `GuestBlogRequest`, `Setting`, `Translation`
- **Admin back-office** — full CRUD for posts, categories (with parent/child **hierarchy**), FAQs, how-to steps, content hooks, guest requests, translations, settings (routes under `/admin`, name prefix `admin.`)
- **JSON API** — token-authenticated (`blog.apikey` middleware) endpoints + a single `gateway` action, for headless publishing
- **SEO** — schema.org JSON-LD (`Article` + `FAQPage` + `HowTo` + `UserLikes`), `<x-blog::seo-meta>` head tags (robots/canonical/Open Graph/Twitter), `sitemap` builder, slugs
- **Search** — cross-content `SearchService` (posts + all related content, categories, tags), public `/search` page + `<x-blog::search-form>` / `<x-blog::top-tags>` components
- **Headers** — SEO response headers (noindex for `?q=` search, pagination canonical) + baseline security headers (HSTS, X-Frame-Options, nosniff, Referrer-/Permissions-Policy, optional CSP)
- **Media** — Spatie MediaLibrary collections + conversions (thumb/preview)
- **i18n** — file translations with DB overrides (admin-editable) via a custom translation loader
- **Settings** — general, cache, and tracking (Google Search Console / GA4) settings + API-key management

## What stays in the app (NOT core)

- The **frontend theme** — layouts, Tailwind build, all public views, public routes/URL structure
- **Calculators / tools** and any **custom domain** (plant DB, product data, …)
- The **`User` model** (the app owns authentication; core treats it as the swappable author model)

## Install

```jsonc
// app composer.json
"repositories": [
  { "type": "vcs", "url": "git@github.com:jonasburneika/blog-cms.git" }
],
"require": { "burneika/blog-cms": "^1.0" }
```

```bash
composer update burneika/blog-cms
php artisan vendor:publish --tag=blog-config   # optional: customise config
php artisan migrate                            # core migrations auto-load
```

The service provider auto-registers (Laravel package discovery). Migrations, views (`blog::` namespace), translations (`blog::`), admin routes, and API routes are wired automatically.

## Configuration & extension seams

Everything customisable lives in `config/blog-cms.php`:

```php
'models' => [
    'user'     => \App\Models\User::class,        // your auth model = the author
    'post'     => \BlogCms\Models\BlogPost::class, // extend & rebind to add relations
    'category' => \BlogCms\Models\BlogCategory::class,
],
'locales' => ['en'],
'media'   => ['conversions' => ['thumb' => [...], 'preview' => [...]]],
```

- **Swap a model** — extend `\BlogCms\Models\BlogPost`, point the config at your subclass. Core resolves the author via `config('blog-cms.models.user')`, never a hardcoded class.
- **Override any admin/schema view** — publish with `--tag=blog-views` and edit the copy in `resources/views/vendor/blog/…`; your copy wins.
- **Structured data in your theme** — drop the core components into your post template:
  ```blade
  <x-blog::schema-article :post="$post" />
  <x-blog::schema-faq     :post="$post" />
  <x-blog::schema-howto   :post="$post" />
  ```
  The `Article` node is automatically enriched from per-post relations (managed in the post editor and via the API):

  | Relation / table | schema.org property | Rendered as |
  |---|---|---|
  | `citations` (`blog_post_citations`) | `citation` | `{ "@type": "WebPage", "@id": <url>, "name": <name> }[]` |
  | `topics` (`blog_post_topics`) | `about` | `{ "@type": "Thing", "name": <name>, "sameAs": <url> }[]` |
  | `basedOn` (`blog_post_based_on`) | `isBasedOn` | flat array of URL strings |

  The API `store`/gateway accept matching `citations[]`, `topics[]`, and `based_on[]` (flat URLs) alongside `faqs`/`howto_steps`.
- **Sitemap** — the app owns URLs, so it passes resolvers:
  ```php
  Route::get('/sitemap.xml', fn () =>
      \BlogCms\Support\SitemapBuilder::make(
          postUrl: fn ($p) => url($p->category->slug.'/'.$p->slug),
          categoryUrl: fn ($c) => url($c->slug),
      )->toResponse(request())
  );
  ```
  Then append your own app-specific URLs (calculators, etc.).

## Redirects & canonicals

Two SEO tools ship in the admin panel (sidebar → **Redirects**, and the *Canonical Page*
field on the post/category SEO tab):

- **Redirect Manager** (`/admin/redirects`) — point any live post or category at another
  post or category (all four combos: category→post, post→post, post→category,
  category→category) with a **301** (permanent) or **302** (temporary) status. Redirects are
  served automatically by the `HandleRedirects` middleware (prepended to the `web` group), so
  no app wiring is needed — visiting the source URL 301/302s to the target's *current* URL.
  The target is resolved live, so a redirect keeps working even if the target is later renamed.
  Lookup paths are kept in sync automatically when a slug or a post's category changes, and a
  redirect is removed if either endpoint is deleted.

- **Canonical page** — optionally mark another post/category as the canonical version of the
  current page (leave as *Self* otherwise). Core stores it and exposes a resolved absolute URL;
  the **app's theme renders the tag**:

  ```blade
  <link rel="canonical" href="{{ $post->canonical_url }}">
  ```

  `canonical_url` falls back to the page's own URL when no custom canonical is set.

> URLs are resolved via `BlogCms\Support\PageUrl`, which follows the same
> `{category}/{post}` / `{category}` structure the token API already commits to. Run
> `php artisan migrate` after upgrading — the middleware queries the `blog_redirects` table on
> front-end requests.

## Multilingual content (spatie/laravel-translatable)

Posts and categories store their text **per locale** as JSON (one row holds all
languages). Slugs are translated too, and non-default locales are served under a URL
prefix (`/lt/…`, `/de/…`; the default locale is unprefixed unless
`blog-cms.locale_url.prefix_default` is true).

- **Languages** are managed in **Settings → Languages**: enable/disable languages and pick
  the default. Values live in the settings table and are read via
  `BlogCms\Support\LocaleManager` (config `blog-cms.locales` / `default_locale` are the seed
  fallback).
- **Editing** is one language at a time: the post/category forms show a language switcher;
  saving merges only the active locale, preserving the others.
- **URLs & hreflang**: `BlogCms\Support\PageUrl::urlFor($model, $locale)` builds the localized
  URL; `PageUrl::alternates($model)` returns `[locale => url]` for every translated locale.
  `$model->canonical_url` resolves in the current locale; `$model->canonicalAlternates()` gives
  the hreflang set. The theme renders `<link rel="canonical">` and `<link rel="alternate"
  hreflang="…">` from these.
- **Sitemap**: `SitemapBuilder::makeMultilingual()` emits one entry per locale with alternates.
- **Redirects** are locale-aware: a localized source URL 301/302s to the target in the same
  locale.

> **Migrating from 1.x (breaking — 2.0):** run `php artisan migrate`. Existing scalar content is
> wrapped into the site default locale; the per-post `locale` column is kept only as an
> informational "source locale". The token API's `locale` param now selects which language to
> read/write (see API.md) and responses resolve translatable fields to that locale.

## Table of contents

Each post gets an auto-generated, in-article table of contents built from its **H2/H3**
headings. Generation happens once, in a `saving` observer (`BlogPostTocObserver`) via
`BlogCms\Services\PostTocGenerator` — never on a frontend request — and is **per locale** (each
translation produces its own TOC, since headings differ by language). The generator uses DOM
parsing (not regex), slugifies headings into unique anchor ids (Lithuanian text transliterated),
injects those ids into the stored HTML, and skips headings inside `nav`/`aside`/`footer` or
elements flagged `data-toc-skip` / classed `related|author|widget|footnote|toc`.

Stored per locale as JSON maps: `toc_items` (nested nav), `content_rendered` (HTML with ids),
`toc_hash` (change detection); plus `toc_enabled` (bool, per post — toggle on the post form) and
`toc_generated_at`.

Render it in the app's post template:

```blade
@include('blog::partials.toc', ['post' => $post])
{!! $post->renderedContent() !!}   {{-- body HTML with heading anchors --}}
```

Read helpers (locale-aware, from `HasTableOfContents`): `$post->tableOfContents($locale)`,
`$post->renderedContent($locale)` (falls back to raw content), `$post->hasTableOfContents($locale)`
(enabled + ≥2 entries). The API's post `list`/`show` responses also include `toc` and
`content_rendered` for the requested locale. Run `php artisan migrate` to add the columns.

## Automatic slug-change redirects

When a **published** post's (or category's) slug changes, the old URL is recorded in
`blog_url_history` and served with a 301 by `HandleRedirects` — so a changed slug never 404s.
This is separate from the manual **Redirect Manager** (entity→entity): history is
old-path→entity and fully automatic. A category rename also records the old URLs of its posts.
Targets resolve live, so a slug that moves A→B→C redirects straight to C (no chains); loops and
live-page shadowing are guarded. Run `php artisan migrate`.

## Authors

Author profiles live in the package (`blog_author_profiles`) so the app's `users` table is
untouched. Each profile links to a user and adds a byline/`display_name`, `role`, **translatable
bio**, avatar (MediaLibrary), social links, and an `is_indexable` flag (thin author pages stay
noindex). Manage them under **Authors** in the admin.

- `$post->authorProfile` — the writer's profile; the **Article schema** author node is enriched
  with job title, avatar `image`, social `sameAs`, and (when indexable) the author-page `url`.
- `BlogAuthorProfile`: `name()`, `avatarUrl()`, `socialLinks()`, `bio` (per locale),
  `publishedPosts()`, `pageUrl()` / `pagePath()` (`author/{slug}`, prefix configurable via
  `blog-cms.author_url.prefix`). The app owns the actual `/author/{slug}` route/template
  (headless) and should `noindex` it unless `is_indexable`.

## Category hierarchy

Categories form a tree via a self-referencing `parent_id`. A **null `parent_id` means the
category is a root** (top level); any other value nests it under that parent. The category
form exposes a **Parent Category** picker whose first option, *“Root (top level)”*, clears the
parent; every other option is labelled with its ancestry path (e.g. `Guides › Beginner`).

- `BlogCategory::parent()` / `children()` — the belongs-to / has-many relations; `children` is
  ordered by `sort_order`. `isRoot()` and the `roots()` scope select top-level categories.
- **Cycle-safe** — the parent picker (and both admin + API validation) exclude the category
  itself and its descendants, so a category can never be nested under its own subtree.
  `subtreeIds()` returns the category plus every descendant; `parentOptionsFor($category)`
  returns the eligible parents.
- Deleting a parent **detaches** its children (`nullOnDelete` → they become roots) rather than
  cascading the delete.
- The **API** accepts `parent_id` on category create/update (same cycle guard) and returns it
  in the serialized payload.

## Media

Two mechanisms:

- **Structured images (MediaLibrary):** covers (`cover_desktop`/`cover_mobile`), post `gallery`,
  category covers, and author `avatar` are Spatie MediaLibrary collections with `thumb`/`preview`
  conversions. Read via `$post->getFirstMediaUrl('cover_desktop', 'preview')`.
- **Inline content images (auto-ingested):** when a post is saved, `ContentImageIngestor` (via a
  `saved` observer) pulls every `<img>` in the body into the post's **`content`** MediaLibrary
  collection — downloading remote URLs and decoding base64 data-URIs — and rewrites each `src` to
  the stored media URL, adding intrinsic `width`/`height`. This runs for **every save path** (token
  API, admin, programmatic) and **per locale**.

  **Lazy-loading is applied to _every_ content image**, not just ingested ones: on save, each
  `<img>` — including the fallback image inside a `<picture>` — is stamped with `loading="lazy"`
  and `decoding="async"` unless the author already set those attributes. So already-local images
  and relative/app paths (which aren't re-fetched) still get lazy-loaded.

  It's idempotent: already-local URLs are skipped for ingestion, and re-sending the same source URL
  reuses the stored file (matched by a `source_url` custom property), so repeated API updates never
  duplicate media (nor re-stamp attributes already present). The TOC/rendered HTML are refreshed
  from the final markup. Disable ingestion with `config('blog-cms.media.ingest_content_images', false)`.

  > So the API can post content HTML with plain external image URLs (or base64) and the images end
  > up hosted in MediaLibrary automatically. Downloads are synchronous on save — queue MediaLibrary
  > conversions in the app if posts carry many images. The app must run `php artisan storage:link`.

  **SSRF-guarded** (`ImageUrlGuard`): remote fetches only run for hosts that resolve to public IPs
  (loopback, link-local `169.254.x`, and RFC1918 ranges are rejected), redirects are disabled, and
  responses are bounded by content-type + `media.max_image_bytes`. Restrict further with
  `media.allowed_image_hosts` (empty = any public host). Blocked/failed fetches are logged and the
  original `src` is left in place — they never break the save.

- **Responsive content images (`srcset`):** every stored content `<img>` gets a `srcset` built from
  width buckets (`media.content_image.srcset_widths`, generated as MediaLibrary conversions named
  `content-{w}`) plus a `sizes` hint, so browsers fetch a right-sized image instead of the full
  upload. **The admin's set size wins** — when the editor's size control has set a width/height, that
  width (and its aspect ratio) drives the `width`/`height` attributes; **when none is set, the image
  is capped at `media.content_image.max_width` keeping its natural aspect ratio.** Buckets wider than
  the original are never used (no upscaling). If the original exceeds `max_width`, `src` is repointed
  at the largest bucket so the oversized file is never served. Bucket URLs are indexed on load, so
  the rewritten `src`/`srcset` are recognised on the next save and never re-ingested.

  > Existing posts from before this feature won't have the `content-{w}` conversions — run
  > `php artisan media-library:regenerate` once to backfill them.

- **WebP / AVIF (`<picture>`):** enable **WebP** and/or **AVIF** in **Settings → Image Optimization**
  (or `media.content_image.formats`). Each stored content image is then wrapped in a `<picture>` with
  a `<source type="image/avif">` / `<source type="image/webp">` (each a width-bucket `srcset`) and the
  original `<img>` as the fallback, so modern browsers get the smaller format and everything else
  still works. The modern formats are MediaLibrary conversions (`content-{w}-{format}`) derived from
  the ShortPixel-optimized original. **AVIF requires an imagick/GD build with AVIF support** — enabling
  a format the driver can't produce will fail those conversions. After enabling, run
  `php artisan media-library:regenerate` to backfill existing images.

- **ShortPixel optimization (queued):** enable it and set the **API key in the admin UI**
  (**Settings → Image Optimization**; stored encrypted in the settings table — no `.env` needed, though
  `media.shortpixel.*` env fallbacks exist for headless setups). On save, each un-optimized content
  image is queued (`OptimizeMediaWithShortPixel`) to the ShortPixel reducer API. The job sends the
  image's public URL, writes the optimized bytes back **in place** (same format), marks the media
  (`shortpixel_optimized` custom property, so it's never re-optimized), and regenerates the `srcset`
  buckets from the optimized original. Compression is `lossy`/`glossy`/`lossless`, with an optional
  **Keep EXIF** toggle (all in the same **Settings → Image Optimization** tab). ShortPixel processes
  asynchronously — a "pending" result re-queues with a delay up to `max_poll_attempts`. **Requires a
  running queue worker**, and content media must be **publicly reachable** by ShortPixel's servers.
  Best-effort: every failure is logged and never disrupts saving.

## Search

Site-wide search lives in `BlogCms\Services\SearchService`. A post is a hit when the term
appears in the **post itself** (title/excerpt/content/meta, in the active locale) **or any of its
related content** — FAQs, how-to steps, topics, citations, or "based on" sources. Categories and
tags (post topics) are searched too.

- `SearchService::search($term, $locale = null, $perPage = null)` → `['term', 'posts' (paginator),
  'categories', 'tags']`. Granular methods: `posts()`, `categories()`, `tags()`, `topTags()`.
- **Public page** — `GET /search?q=…` (route name `blog.search`) renders `blog::search`. Set
  `config('blog-cms.search.route')` to `null` if your app owns `/?q=…` on its home route and calls
  `SearchService` directly instead. Configure the query param (`q`) and `per_page` in the same block.
- **Search results are never indexed** — any request carrying the search query param gets
  `noindex, follow` (see below), so `/?q=…` and `/search?q=…` stay out of Google.
- **Tags** are backed by **post topics** (`BlogPostTopic`). "Top tags" = the most-used topic names
  across published posts (`SearchService::topTags()`, count from `config('blog-cms.tags.top_count')`).

Two front-end Blade components (usable anywhere, e.g. in a themed layout):

- `<x-blog::search-form />` — a search bar posting to the configured route/param.
- `<x-blog::top-tags />` — the top-N tags, each linking to a search; self-fetches if no `:tags` passed.

## SEO head tags (`<x-blog::seo-meta>`)

Drop `<x-blog::seo-meta />` into your layout's `<head>` on every public page. It emits the
`<meta name="robots">` tag (defaulting to **`index, follow`**, so audits stop flagging it as
missing), `<link rel="canonical">` when paginating, and a **complete Open Graph + Twitter card**
set (`og:*`, `twitter:card/site/title/description/image`). All props are optional and fall back to
the settings table (`site_name`, `homepage_meta_*`, `site_description`) and
`config('blog-cms.social.*')`, so even a bare front page gets a full set:

```blade
<x-blog::seo-meta title="{{ $post->title }}" description="{{ $post->meta_description }}"
                  image="{{ \BlogCms\Support\SchemaBuilder::coverUrl($post) }}" type="article" />
```

The robots value and pagination canonical come from `BlogCms\Support\SeoMeta`, the same source the
response headers use — so the header and the meta tag never disagree.

### Robots & canonical response headers

`AddRobotsHeader` (on the whole `web` group) sets, on every response:

- `X-Robots-Tag: index, follow` normally; `noindex, follow` for **search** pages (`?q=`);
  `noindex, nofollow` for `admin/*`, `login`, `register`.
- `Link: <primary>; rel="canonical"` for **paginated** URLs (`?page=N`) → the primary page is the
  same URL with `page` stripped (other params kept), so page 2..N don't compete with page 1.

## Security headers

`SecurityHeaders` (on the `web` group) adds a baseline set to every response, all configurable under
`config('blog-cms.security_headers')` (set any to `null` to skip; existing headers are never
overwritten):

- `X-Frame-Options: SAMEORIGIN` (clickjacking), `X-Content-Type-Options: nosniff` (MIME sniffing),
  `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (locks down camera/mic/geo).
- `Strict-Transport-Security` — **only sent over HTTPS**. Add `; preload` once your HTTPS is stable.
- **CSP is off by default** (a wrong policy silently breaks a site). Set `content_security_policy`
  (or trial with `content_security_policy_report_only`) to enable it.

## 404 page

Ship a 404 that keeps the visitor on the site. Publish the example into your app's error views:

```
php artisan vendor:publish --tag=blog-frontend   # → resources/views/errors/404.blade.php
```

It **extends your app layout** (`config('blog-cms.frontend.layout')`, with the content section from
`…frontend.section`) so the header/footer — and navigation — are preserved, and it offers a search
bar plus the top tags so users can recover. Leave `frontend.layout` `null` to use the package's
minimal standalone layout (`blog::layouts.public`), which still includes a home link + search.

## Like counts (schema.org UserLikes)

Posts carry a `likes_count` column, surfaced in the Article JSON-LD as
`interactionStatistic` → `InteractionCounter` with `interactionType`
`https://schema.org/UserLikes`. The app owns the actual "like" action/endpoint; core just stores the
count and renders the structured data.

## What the consuming app must still provide

Core is deliberately not 100% turnkey — a few things are the app's job by design:

- **Auth scaffolding** — the admin routes use the `auth` middleware, but login/registration views, the auth guard, and the `User` model live in the app. Point `config('blog-cms.models.user')` at your `User`.
- **Admin assets** — `resources/views/vendor/blog/layouts/admin.blade.php` (once published) loads its CSS/JS via `@vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])` plus three CDNs (Bunny Fonts, Jodit editor, Alpine.js). The app must provide those Vite entry points (Tailwind build). Shipping a self-contained compiled admin bundle in the package is planned but not yet done — see `resources/dist/`.
- **API author** — posts created via the token API are attributed to `config('blog-cms.api.default_author_id')` (env `BLOG_CMS_API_AUTHOR_ID`, default `1`), because the API key authenticates the request, not a person.
- **Frontend** — the entire public theme, routes, and URL structure (the sitemap/schema helpers plug into whatever URLs you choose).

## Gotchas

- **`status` is typed differently per model.** `BlogPost::$status` is an **integer** enum (`0`/`1`/`2` via `BlogPost::STATUS_DRAFT`/`STATUS_PUBLISHED`/`STATUS_ARCHIVED`), while `BlogCategory::$status` is a **string** enum (`active`/`inactive`). Use the post constants when querying posts (`where('status', BlogPost::STATUS_PUBLISHED)`, not `where('status', 'published')`).
- **Categories use `title`, not `name`.** The original `name` column was replaced by a translatable `title` (plus `menu_title`). Read category labels via `$category->title`.
- **Pin the right version.** `^1.0` currently resolves to the latest `1.x` tag — make sure that tag actually includes the features you rely on (authors, redirects, canonicals, multilingual, TOC, category hierarchy). When in doubt, check `git tag` / the release notes before upgrading.

## Versioning

Semver. Breaking changes bump major; apps upgrade on their own schedule via the `^1.0` constraint. Fix a bug once here, tag a release, `composer update` everywhere.
