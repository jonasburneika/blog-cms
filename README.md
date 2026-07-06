# blog-cms

A reusable, **headless-ish blog CMS core** for Laravel 12. It's a deliberately *boring* blog: models, admin management, schema.org structured data, media optimization, DB-backed translations, guest blogging, multi-author, and cache/tracking settings — and **nothing site-specific**. No frontend theme, no calculators, no custom domain models. Those live in each consuming app.

Extracted so multiple sites (e.g. a brewery blog, a soil-science blog) share one maintained core and only build their own theme + domain on top.

## What's in core

- **Models** — `BlogPost`, `BlogCategory`, `BlogPostFaq`, `BlogPostHowToStep`, `BlogPostHook`, `HookTemplate`, `GuestBlogRequest`, `Setting`, `Translation`
- **Admin back-office** — full CRUD for posts, categories, FAQs, how-to steps, content hooks, guest requests, translations, settings (routes under `/admin`, name prefix `admin.`)
- **JSON API** — token-authenticated (`blog.apikey` middleware) endpoints + a single `gateway` action, for headless publishing
- **SEO** — schema.org JSON-LD (`Article` + `FAQPage` + `HowTo`) as drop-in Blade components, `sitemap` builder, slugs
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

## What the consuming app must still provide

Core is deliberately not 100% turnkey — a few things are the app's job by design:

- **Auth scaffolding** — the admin routes use the `auth` middleware, but login/registration views, the auth guard, and the `User` model live in the app. Point `config('blog-cms.models.user')` at your `User`.
- **Admin assets** — `resources/views/vendor/blog/layouts/admin.blade.php` (once published) loads its CSS/JS via `@vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])` plus three CDNs (Bunny Fonts, Jodit editor, Alpine.js). The app must provide those Vite entry points (Tailwind build). Shipping a self-contained compiled admin bundle in the package is planned but not yet done — see `resources/dist/`.
- **API author** — posts created via the token API are attributed to `config('blog-cms.api.default_author_id')` (env `BLOG_CMS_API_AUTHOR_ID`, default `1`), because the API key authenticates the request, not a person.
- **Frontend** — the entire public theme, routes, and URL structure (the sitemap/schema helpers plug into whatever URLs you choose).

## Versioning

Semver. Breaking changes bump major; apps upgrade on their own schedule via the `^1.0` constraint. Fix a bug once here, tag a release, `composer update` everywhere.
