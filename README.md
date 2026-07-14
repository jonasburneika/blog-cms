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
bio**, avatar (MediaLibrary), verification links, areas of expertise, credentials, and an
`is_indexable` flag (thin author pages stay noindex). Manage them under **Authors** in the admin.

The author profile is an **E-E-A-T** signal builder — the **Article schema**'s `author` `Person`
node is enriched from it:

- **`url`** → the internal `/author/{slug}` page (when `is_indexable`) — the definitive identity
  anchor for the writer, per Google's Knowledge Graph guidance.
- **`description`** ← the bio (aim for a specific ~150-word paragraph; the form shows a word count).
- **`jobTitle`** ← role, **`image`** ← avatar.
- **`sameAs`** ← the profiles/verification links (personal site, GitHub, a hosted CV/certificate
  PDF, Medium, professional Reddit/forum profiles — not only mainstream social).
- **`knowsAbout`** ← areas of expertise (one per line).
- **`hasCredential`** ← credentials as `EducationalOccupationalCredential` (name + category, e.g.
  "Permaculture Design Certification" / "Certification").

**Editorial review (`reviewedBy`):** posts have a **Reviewed by** picker (`reviewed_by` → a user
with an author profile). When set, the Article schema gets a `reviewedBy` `Person` (fully enriched
like the author), so a newer author can inherit authority from an established expert who vetted the
article.

**Captioned images (`ImageObject`):** the Article's `image` is emitted as `ImageObject`(s) with
`caption` — the cover (caption from meta/title) plus any body `<img>` that carry **alt text** (their
alt becomes the caption). Give original, first-hand photos descriptive alt that echoes the article's
core keywords.

- `BlogAuthorProfile`: `name()`, `avatarUrl()`, `socialLinks()`, `knowsAbout()`, `credentials()`,
  `bio` (per locale), `publishedPosts()`, `pageUrl()` / `pagePath()` (`author/{slug}`, prefix
  configurable via `blog-cms.author_url.prefix`). The app owns the actual `/author/{slug}`
  route/template (headless) and should `noindex` it unless `is_indexable`.

> Publisher / `mainEntityOfPage` stay app-owned (they need your domain, org name and logo) — set
> them in the app's schema wrapper around `SchemaBuilder::article()`.

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
  (or `media.content_image.formats`). **ShortPixel does the conversion** (via its `convertto`), so
  **no local imagick/AVIF support is needed** — it requires ShortPixel enabled + a queue worker. The
  ShortPixel job stores each converted image as a sibling variant media (`content-webp`/`content-avif`,
  linked to the source), then re-runs the ingestor to wrap each content image in a `<picture>` with a
  `<source type="image/avif">` / `<source type="image/webp">` (each a width-bucket `srcset`) and the
  original `<img>` as the fallback. So modern browsers get the smaller format and everything else still
  works. Because the conversion is asynchronous, the `<picture>` appears once the optimization job has
  run (not in the initial save response).

- **ShortPixel optimization (queued):** enable it and set the **API key in the admin UI**
  (**Settings → Image Optimization**; stored encrypted in the settings table — no `.env` needed, though
  `media.shortpixel.*` env fallbacks exist for headless setups). On save, each un-optimized content
  image is queued (`OptimizePostImages`, one job per post) to the ShortPixel reducer API. The job
  writes the optimized bytes back **in place** (same format), marks the media (`shortpixel_optimized`
  custom property, so it's never re-optimized), and regenerates the `srcset` buckets. Compression is
  `lossy`/`glossy`/`lossless`, with an optional **Keep EXIF** toggle (all in the same
  **Settings → Image Optimization** tab). Downloaded bytes are **validated as real images** and only
  written when actually smaller, so a bad response never corrupts your media.

  - **Original backup / fallback:** before the first optimization, a **pristine copy of each original**
    is kept beside the media on the same disk (`media.shortpixel.keep_backup`, default on; removed when
    the media is deleted). This is the fallback if an optimization goes wrong, and — importantly — the
    source for re-optimization, so images are never re-compressed on top of an already-lossy file.
  - **Reoptimize all:** **Settings → Image Optimization → Reoptimize all images** queues
    `ReoptimizeAllImages`, which dispatches a per-post `OptimizePostImages(reoptimize: true)`. Each
    restores its images from backup, drops the existing WebP/AVIF variants, and reruns them through the
    **current** settings (compression + formats). All work is done in **queue jobs**. ShortPixel processes
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

> **"Robots meta tag missing" in an audit?** The `X-Robots-Tag` **header** is always sent, but the
> `<meta name="robots">` **tag** only appears if you render `<x-blog::seo-meta />` in your `<head>`.
> Add it to your layout (see the homepage example above) and the tag shows `index, follow`.

### Robots & canonical response headers

`AddRobotsHeader` (on the whole `web` group) sets, on every response:

- `X-Robots-Tag: index, follow` normally; `noindex, follow` for **search** pages (`?q=`);
  `noindex, nofollow` for `admin/*`, `login`, `register`.
- `Link: <primary>; rel="canonical"` for **paginated** URLs (`?page=N`) → the primary page is the
  same URL with `page` stripped (other params kept), so page 2..N don't compete with page 1.

### robots.txt

The package serves `GET /robots.txt` (config `blog-cms.robots_txt`, on by default) — it disallows
`admin`/`login`/`register`/guest-submit paths, keeps **search + tag URLs out of the crawl**
(`disallow_query_params`, default `['q']` → `Disallow: /*?q=` + `Disallow: /*&q=`, since tag links
point at the search with the tag as `q`), and points at your sitemap. Those `?q=` pages are also
`noindex` via the response header, so they're covered both ways. **A static `public/robots.txt` is
served by the web server first and wins**, so delete it (Laravel ships a default one) to use the
generated version, or set `robots_txt.enabled = false` and keep your own.

The **sitemap** is referenced by the `Sitemap:` line (config `robots_txt.sitemap_url`, default
`url('/sitemap.xml')`) so crawlers find it, but it's kept **out of the search index**: paths in
`robots_txt.noindex_paths` (default `sitemap*.xml`) are served with `X-Robots-Tag: noindex` — still
crawlable, never a public search result. (This only applies if the sitemap is a Laravel route, which
it is when you serve it via `SitemapBuilder`; a static `public/sitemap.xml` needs the header set at
the web-server level.)

## Social share buttons

`<x-blog::share-buttons :post="$post" />` renders post share buttons that **append UTM parameters** to
the shared URL, so social traffic is attributed in analytics. Networks (config
`blog-cms.sharing.networks`, any of `twitter`, `facebook`, `linkedin`, `whatsapp`, `reddit`, `email`,
`copy`) each set `utm_source=<network>` + `utm_medium` (`social`, or `referral` for copy / `email` for
email) + `utm_campaign` (config `sharing.campaign`, overridable per widget). Example on a post:

```blade
<x-blog::share-buttons :post="$post" campaign="blog-post"
                       :networks="['twitter','facebook','linkedin','copy']" />
```

Theme-neutral — style the emitted `.blog-share*` classes. The small vanilla-JS handler is inlined
once per page (event-delegated), strips any existing `utm_*` before tagging, and needs no build step.

## Social profiles + RSS

**Social profile links** — set the site's social media accounts under **Settings → General → Social
media links** (label + URL). Render them anywhere (footer/header):

```blade
<x-blog::social-links class="footer__social" />
```

It emits a `<ul>` of `target="_blank" rel="noopener noreferrer me"` links (styled via `.blog-social*`),
nothing when none are set. Stored in the settings table (`social_media_links`), so they're also handy
as your Organization `sameAs`. Read directly with `BlogCms\Models\Setting::get('social_media_links')`.

**RSS feed** — the package serves an RSS 2.0 feed of the newest published posts at **`GET /feed`**
(config `blog-cms.rss` — route, `limit`, optional `title`/`description`, or disable) so readers can
subscribe. `<x-blog::seo-meta>` advertises it with a `<link rel="alternate" type="application/rss+xml">`
autodiscovery tag, so browsers/readers find it automatically. Items carry the title, link, `pubDate`,
excerpt (`description`) and full HTML (`content:encoded`), in the current locale.

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

## Guest blogging (end-to-end, two-step)

A non-technical guest can go from a pitch to a published article without ever touching `/admin`.
Enable with `config('blog-cms.guest_blogging.enabled')` (env `BLOG_CMS_GUEST_BLOGGING`).

**Flow:** guest submits a **pitch** → editor **approves** (mints a token + emails the guest a private
`/{prefix}/submit/{token}` link) → guest **submits the full article** → a **draft** `BlogPost` is
created, attributed to a guest author → editor reviews & publishes it in the normal posts admin.

- **Public endpoints** (registered only when enabled, write routes **throttled**, name prefix
  `blog.guest.`): `GET/POST /guest-blog` (pitch) and `GET/POST /guest-blog/submit/{token}` (article),
  via `GuestBlogController` + `GuestPitchRequest`/`GuestArticleRequest`. Each form has a **honeypot**
  (`website` field, must stay empty). Minimal Blade views (`blog::guest-blog.*`) extend your
  front-end layout (`blog-cms.frontend.layout`) and are publishable/overridable.
- **`GuestBlogService`** does the work: `approve()` (mint token + `GuestPostApproved` event +
  config-gated `GuestPitchApprovedMail`), `reject()` (`GuestPostRejected` + mail, creates nothing),
  and `createDraftFromSubmission()` — resolve/create a **locked guest-author user** (reused by email)
  + `BlogAuthorProfile`, create the draft in the mapped category, and run the **normal save pipeline**
  (TOC, content-image ingest, srcset). Falls back to `api.default_author_id` if user creation is off
  or fails.
- **Idempotent & safe:** approving is a no-op if already approved; a submission won't create a second
  post (`created_post_id` guards it); guest HTML is **sanitized against an allowlist**
  (`BlogCms\Support\HtmlSanitizer`, config `guest_blogging.allowed_html`) — scripts, event handlers
  and `javascript:` URLs are stripped — before it is ever rendered.
- **Audit:** `guest_blog_requests` gains `title`/`content`/`excerpt`/`locale`, `submit_token`/
  `submitted_at`, and `created_post_id`/`created_user_id`. The cover is a MediaLibrary `cover`
  collection copied onto the post. In the admin request page you get the submit link (to re-share) and
  an **Open draft** button once submitted.

> Creating guest-author **users** writes to the app's `users` table (name/email + an unguessable
> password → effectively no login). Turn it off with `guest_blogging.auto_create_author = false` to
> attribute drafts to `api.default_author_id` instead. Notifications require the app's mail config.
>
> **Authorship-spoofing guard:** the pitch email is unverified, so the flow only ever reuses an
> existing account that is itself a **guest author** (`blog_author_profiles.is_guest`). If the email
> matches any other account (e.g. a staff/admin), the draft is attributed to the default author, never
> to that account — so a guest post can't borrow a credentialed author's identity or E-E-A-T.

## Content blocks (homepage copy & co.)

Reusable **rich-text (WYSIWYG) blocks** the theme renders by key — e.g. copy shown **before and
after the homepage featured posts**. Manage them under **Content Blocks** in the admin; the content
editor is the same Jodit WYSIWYG as posts. Title + content are **translatable**.

- Render in your theme: `<x-blog::content-block key="homepage_before_featured" />` … featured posts …
  `<x-blog::content-block key="homepage_after_featured" />` (both keys are suggested in the admin;
  use any keys you like). Or fetch with `BlogContentBlock::forKey($key)`.
- Renders nothing when the block is missing, inactive, or empty. Content is admin-authored (trusted)
  and output as raw HTML. Table: `blog_content_blocks` (`key`, `name`, translatable `title`/`content`,
  `is_active`, `sort_order`).

## Simple pages (Terms, Privacy, …)

Standalone content pages authored in the admin (**Pages**) with the Jodit WYSIWYG — Terms &
Conditions, Privacy Policy, Cookie Policy, About, etc. Title / slug / content / meta are
**translatable**; the slug is auto-generated per locale from the title (override it if you like).
Each page has a **draft/published** status and an `is_indexable` flag.

**Pages are first-class URLs**, integrated into `PageUrl` alongside posts/categories: they appear in
the page picker (canonical / link-list / redirect targets), in the **sitemap** (published + indexable),
and get **automatic slug-change 301s** and delete-cleanup like posts/categories. `pages.url_prefix`
shapes the path — **default `page`** → `/page/{slug}` (its own namespace, no collision with root
categories); set `''` for root-level `/{slug}` (but then a page and a category can't share a slug).
The prefix can be **translated** by giving a per-locale array, e.g.
`['en' => 'page', 'lt' => 'puslapis', 'de' => 'seite']` → `/page/privacy-policy`, `/lt/puslapis/…`.

Headless — **the app owns the route and template**. Serve them from the same catch-all that resolves
posts/categories (so they're reachable at their URL like any other page):

```php
// routes/web.php (your app) — resolve category / post / page from one catch-all
Route::get('/{a}/{b?}', function (string $a, ?string $b = null) {
    $locale = \BlogCms\Support\LocaleManager::current();
    $entity = $b
        ? \BlogCms\Support\PageUrl::postBySlug($a, $b, $locale)
        : (\BlogCms\Support\PageUrl::categoryBySlug($a, $locale)
            ?: \BlogCms\Support\PageUrl::pageBySlug($a, $locale));
    abort_if(! $entity, 404);
    // dispatch to the right template by type…
    return $entity instanceof \BlogCms\Models\BlogPage
        ? view('page', ['page' => $entity])
        : /* your post/category views */ abort(404);
})->where('a', '[a-z0-9-]+');
```

Or a dedicated route: `\BlogCms\Models\BlogPage::findPublished($slug, $locale)`.

```blade
{{-- resources/views/page.blade.php (your app) --}}
@extends('layouts.app')
@section('content')
    <article class="prose mx-auto">
        <h1>{{ $page->title }}</h1>
        {!! $page->content !!}   {{-- trusted admin HTML --}}
    </article>
@endsection
```

`BlogPage::findPublished($slug, $locale)` looks up a published page by slug in the (current) locale.
`pagePath()`/`pageUrl()` build the URL from `config('blog-cms.pages.url_prefix')`. Pass the page's
`meta_title`/`meta_description` to `<x-blog::seo-meta>`, and honour `is_indexable` (render
`<meta name="robots" content="noindex">` when false). Because pages are in the page picker now, link
them from the footer by choosing the page directly in a **link list** (no need for an external URL).

## Link lists (footer & co.)

Manage named lists of links (e.g. a **footer**) under **Link Lists** in the admin. Each link has a
**translatable anchor**, points at a **blog post**, a **blog category** (via the shared page picker),
or an **external URL**, and has a **target** (`_self`/`_blank`/`_parent`/`_top`) + optional `rel`.

- Render in your theme by key: `<x-blog::link-list key="footer" class="footer__links" />`, or fetch the
  data with `BlogLinkList::forKey('footer')` → `$list->activeLinks`.
- `BlogLink` resolves internal targets through `PageUrl` (locale-aware). Inactive links and **dead
  internal targets** (a deleted post/category) are skipped when rendered; external `_blank` links get
  `rel="noopener noreferrer"` automatically unless you set `rel` explicitly.
- Tables: `blog_link_lists` (`key`, `name`, `sort_order`) and `blog_links` (translatable `label`,
  `link_type`/`link_id` **or** `url`, `target`, `rel`, `sort_order`, `is_active`).

## Front-end example: homepage + footer

The package is headless — you own the theme — but its components drop straight into your Blade. Put
the SEO head tags in your layout so every page gets `<meta name="robots" content="index, follow">`,
canonical and Open Graph/Twitter:

```blade
{{-- resources/views/layouts/app.blade.php (your app) — in <head> --}}
<x-blog::seo-meta
    :title="$metaTitle ?? null"
    :description="$metaDescription ?? null" />
```

A homepage with editor copy **before and after** the featured posts:

```blade
{{-- resources/views/home.blade.php (your app) --}}
@extends('layouts.app')

@section('content')
    <x-blog::content-block key="homepage_before_featured" class="prose mx-auto my-8" />

    <section class="featured-posts">
        @foreach($featuredPosts as $post)   {{-- your own query, e.g. BlogPost::readable()->where('is_featured', true) --}}
            <article>
                <a href="{{ \BlogCms\Support\PageUrl::urlFor($post) }}">{{ $post->title }}</a>
            </article>
        @endforeach
    </section>

    <x-blog::content-block key="homepage_after_featured" class="prose mx-auto my-8" />
@endsection
```

On a **post page**, add the share buttons (they resolve the post's canonical URL + title):

```blade
<x-blog::share-buttons :post="$post" campaign="blog-post" class="mt-12 flex flex-wrap gap-3" />
```

A footer that renders the managed **footer** link list (create a list with key `footer` in the admin,
add its links, then):

```blade
{{-- resources/views/partials/footer.blade.php (your app) --}}
<footer class="site-footer">
    <nav aria-label="Footer">
        <x-blog::link-list key="footer" class="site-footer__links" />
    </nav>
    <p class="site-footer__copy">&copy; {{ now()->year }} {{ \BlogCms\Models\Setting::get('site_name', config('app.name')) }}</p>
</footer>
```

Both components render **nothing** when the block/list is missing, inactive, or empty, so they're safe
to leave in the template before you've created the content. Style the emitted classes
(`blog-content-block__*`, `blog-link-list__*`) — or pass your own via `class="…"` — in your theme.

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

## AutoSEO Integration

Pull content **from an [AutoSEO](https://autoseo) instance into this CMS**. The importer syncs
**categories, authors, and posts** — along with each post's **FAQ / HowTo / citations / topics /
keywords** and their **cover images + author avatars** — into the local models, **idempotently**: a
`blog_autoseo_map` table maps each AutoSEO resource to the CMS row it created, so re-running updates
in place and never duplicates.

### Configuration

Set the AutoSEO instance + token in `.env` (or override per-site in the settings table):

| Env var | Settings-table key | Description |
|---|---|---|
| `AUTOSEO_BASE_URL` | `autoseo_base_url` | Base URL of the AutoSEO instance (e.g. `https://app.autoseo.example`) |
| `AUTOSEO_API_TOKEN` | `autoseo_api_token` | API token — issue it in AutoSEO with a **read-only, site-scoped** preset |
| `AUTOSEO_SITE_ID` | `autoseo_site_id` | The AutoSEO site to import from |
| `AUTOSEO_TIMEOUT` | — | Per-request HTTP timeout in seconds |
| `AUTOSEO_DOWNLOAD_MEDIA` | — | Set `false` to skip downloading cover images/avatars |
| `AUTOSEO_AUTHOR_EMAIL_DOMAIN` | — | Domain used to synthesise emails for imported authors |
| `AUTOSEO_SYNC_SCHEDULE` | — | Auto-run the sync: a preset (`daily`, `hourly`, `twiceDaily`, …) or a cron expression (`0 4 * * *`); empty = off |

Settings-table overrides (`autoseo_base_url` / `autoseo_api_token` / `autoseo_site_id`) win over the
env values, so a multi-site host can point each app at a different AutoSEO site without redeploying.

> The token only needs **read** access to one site — issue it in AutoSEO from the read-only,
> site-scoped preset. Nothing is written back to AutoSEO.

### Usage

Run the importer with the artisan command:

```bash
php artisan blog-cms:autoseo-sync                 # sync everything (categories, authors, posts)
php artisan blog-cms:autoseo-sync --only=posts    # limit to one resource (repeatable)
php artisan blog-cms:autoseo-sync --dry-run       # report what would change without writing
php artisan blog-cms:autoseo-sync --force         # re-import even when payloads are unchanged
php artisan blog-cms:autoseo-sync --ping          # only test connectivity/credentials, then exit
```

On success it prints a per-resource table of `created` / `updated` / `skipped` / `failed` counts.
`--only` accepts `categories`, `authors`, `posts` (repeat the flag to combine); an unknown value
errors out.

### Scheduling

The package schedules itself — set one env var and the sync runs automatically:

```dotenv
AUTOSEO_SYNC_SCHEDULE=daily        # a preset: daily, hourly, twiceDaily, weekly, …
AUTOSEO_SYNC_SCHEDULE="0 4 * * *"  # …or any five-part cron expression
```

The scheduled run executes the `blog-cms:autoseo-sync` command (`withoutOverlapping`, in the
background), so **no queue worker is needed** — only the standard Laravel scheduler must be running
in the host app (the usual `* * * * * php artisan schedule:run` cron entry, or `schedule:work`).
Leave the variable unset to disable.

Prefer to control it from the host app instead? A queued job ships alongside the command:

```php
// app/Console/Kernel.php (your app)
$schedule->job(new \BlogCms\Jobs\SyncAutoSeoContent())->daily();
```

`SyncAutoSeoContent(array $only = [], bool $force = false)` runs the same sync on the queue
(`timeout` 1800s, a single attempt — the sync is idempotent, so a failed run just retries on the next
tick). The job route **does** require a running queue worker.

### Notes

- **Idempotent** — re-running updates existing rows in place (matched via `blog_autoseo_map`); it
  never creates duplicates.
- **Translatable posts** — AutoSEO locale variants that share a `locale_group_id` are **merged into
  one translatable post** (one row, per-locale content), matching the CMS's spatie/laravel-translatable model.
- **Real save pipeline** — posts import through **Eloquent**, so the TOC, redirect/URL-history, and
  inline-image observers all fire (imported bodies get their table of contents, slug-change 301s, and
  content-image ingestion just like admin/API saves).
- **Inline images** — AutoSEO's host is **auto-allowed** for content-image ingestion, so inline
  `<img>` tags in imported bodies are pulled into MediaLibrary (SSRF guard notwithstanding).
- **Status mapping** — AutoSEO `draft` / `review` → **draft**; `published` → **published**.
- **Citations per post** — a post's citations import from its `schema_links`. AutoSEO's **site-level
  citation library** has no CMS counterpart and is not imported.
- **Change notes skipped** — AutoSEO post change-notes have no CMS counterpart and are ignored.

## Versioning

Semver. Breaking changes bump major; apps upgrade on their own schedule via the `^1.0` constraint. Fix a bug once here, tag a release, `composer update` everywhere.
