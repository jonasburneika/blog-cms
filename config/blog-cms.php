<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Model bindings
    |--------------------------------------------------------------------------
    | The consuming app owns authentication, so `user` points at the app's User
    | model (the blog author). Post/category are swappable so an app can extend
    | them (e.g. add a plants() relation) without forking core. Core code must
    | resolve these via config(), never reference the classes directly.
    */
    'models' => [
        'user'     => \App\Models\User::class,
        'post'     => \BlogCms\Models\BlogPost::class,
        'category' => \BlogCms\Models\BlogCategory::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    | Posts created through the token-authenticated API aren't tied to a logged-in
    | user (the API key authenticates the request, not a person). This is the
    | author (users.id) that API-created posts are attributed to. Set it per app.
    */
    'api' => [
        'default_author_id' => env('BLOG_CMS_API_AUTHOR_ID', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    | Seed/fallback list of content locales and the default locale. These are
    | only the initial values — the admin "Settings → Languages" page persists
    | overrides in the settings table, which BlogCms\Support\LocaleManager reads
    | first. Content (posts/categories) is stored per-locale via
    | spatie/laravel-translatable.
    */
    'locales' => ['en'],

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Locale URLs
    |--------------------------------------------------------------------------
    | Non-default locales are served under a path prefix (/lt/…, /de/…). Set
    | prefix_default to true to also prefix the default locale (/en/…). Slugs
    | themselves are translated per locale.
    */
    'locale_url' => [
        'prefix_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Author pages
    |--------------------------------------------------------------------------
    | Path prefix for author profile pages (the app owns the actual route/view).
    | e.g. prefix "author" → /author/{slug}.
    */
    'author_url' => [
        'prefix' => 'author',
    ],

    /*
    |--------------------------------------------------------------------------
    | Front-end (search + error pages)
    |--------------------------------------------------------------------------
    | The package ships a search results page and an example 404 page. They are
    | headless-friendly: point `layout` at your app's Blade layout and `section`
    | at the content section it yields, and the pages inherit your header/footer
    | so visitors never lose navigation. Leave `layout` null to use the package's
    | minimal standalone skeleton.
    |
    | `search.route` is the public search URL (set to null to not register the
    | package route — e.g. if the app handles `/?q=` on its own home route and
    | just calls BlogCms\Services\SearchService directly).
    */
    'frontend' => [
        'layout' => null,        // e.g. 'layouts.app'
        'section' => 'content',  // the @yield/@section name in that layout
    ],

    'search' => [
        'route' => 'search',     // GET /search?q=… ; null disables the route
        'per_page' => 12,
        'query_param' => 'q',
    ],

    'tags' => [
        // "Tags" are backed by post topics (BlogPostTopic). How many to surface
        // in the "top tags" widget (404 page, search sidebar).
        'top_count' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social / Open Graph defaults
    |--------------------------------------------------------------------------
    | Fallbacks for the <x-blog::seo-meta> component's Open Graph + Twitter card
    | tags. Per-page values (passed as props) win; these fill the gaps. Site
    | name / descriptions also fall back to the matching rows in the settings
    | table (site_name, site_description, homepage_meta_*).
    */
    'social' => [
        'twitter_site' => env('BLOG_CMS_TWITTER_SITE'), // e.g. "@yoursite"
        'twitter_card' => 'summary_large_image',
        'og_image' => env('BLOG_CMS_OG_IMAGE'),         // absolute URL fallback
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    | Sent on every web response by BlogCms\Http\Middleware\SecurityHeaders.
    | Set any value to null to skip that header. HSTS is only emitted over HTTPS.
    | CSP is null by default (a wrong policy silently breaks a site) — set a
    | string to enable it, or use the report-only key to trial one first.
    */
    'security_headers' => [
        'enabled' => true,
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'permissions_policy' => 'geolocation=(), camera=(), microphone=()',
        'hsts' => 'max-age=31536000; includeSubDomains', // add "; preload" once stable
        'content_security_policy' => null,
        'content_security_policy_report_only' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media conversions
    |--------------------------------------------------------------------------
    | Sizes generated by Spatie MediaLibrary for post imagery.
    */
    'media' => [
        'conversions' => [
            'thumb'   => ['width' => 400, 'height' => 300],
            'preview' => ['width' => 800, 'height' => 600],
        ],

        // On save, inline <img> in post content are pulled into MediaLibrary
        // (this collection) and their src rewritten to the stored URL. Set to
        // false to leave content images untouched.
        'ingest_content_images' => true,
        'content_collection' => 'content',

        // SSRF guard for fetching remote content images. Remote hosts must
        // resolve to public IPs; optionally restrict to an explicit allowlist
        // (empty = any public host). Fetches disable redirects and are bounded
        // by size + timeout.
        'allowed_image_hosts' => [],
        'max_image_bytes' => 15 * 1024 * 1024,
        'fetch_timeout' => 15,

        // Responsive content images. On save, each stored content <img> gets a
        // srcset built from these width buckets (only those <= the original's
        // width, never upscaled) + a sizes attribute, so browsers download a
        // right-sized image instead of the full upload. src stays the original
        // (fallback). width/height come from the admin's set size when present,
        // else the natural ratio capped at max_width. Buckets are generated as
        // MediaLibrary conversions (see BlogPost::registerMediaConversions).
        'content_image' => [
            'max_width' => 1600,
            'srcset_widths' => [480, 768, 1024, 1600],
            'sizes' => '(max-width: 768px) 100vw, 768px',

            // Modern output formats served via <picture><source> (original stays
            // the <img> fallback). The conversion is done by ShortPixel (its
            // `convertto`), so it needs ShortPixel enabled + a queue worker; no
            // local imagick/AVIF support is required. The admin "Image
            // Optimization" tab overrides this. Supported: 'webp', 'avif'.
            'formats' => [],
        ],

        // ShortPixel image optimization (https://shortpixel.com). When enabled,
        // saving a post queues a job per stored content image that sends its
        // public URL to the ShortPixel reducer API, then writes the optimized
        // bytes back (same format, in place) and regenerates the srcset
        // conversions. Best-effort: needs a queue worker, an API key, and content
        // media publicly reachable by ShortPixel's servers.
        //
        // The API key, enable toggle and compression are managed from the admin
        // Settings → Image Optimization tab (stored in the settings table, key
        // encrypted). The env values below are only fallbacks for headless setups.
        'shortpixel' => [
            'enabled' => (bool) env('BLOG_CMS_SHORTPIXEL_ENABLED', false),
            'api_key' => env('SHORTPIXEL_API_KEY'),
            'compression' => env('BLOG_CMS_SHORTPIXEL_COMPRESSION', 'lossy'), // lossy|glossy|lossless
            'keep_exif' => (bool) env('BLOG_CMS_SHORTPIXEL_KEEP_EXIF', false),
            // Keep a pristine copy of each original before optimizing, so we can
            // always re-optimize from the source (not a re-compressed file) and
            // fall back if an optimization goes wrong. Stored beside the media on
            // the same disk; removed automatically when the media is deleted.
            'keep_backup' => (bool) env('BLOG_CMS_SHORTPIXEL_KEEP_BACKUP', true),
            'queue' => env('BLOG_CMS_SHORTPIXEL_QUEUE'),   // null = default queue
            'wait' => 30,          // seconds the API waits before returning "pending" (0-30)
            'max_poll_attempts' => 6,
            'poll_delay' => 20,    // seconds between re-checks while pending
        ],
    ],

];
