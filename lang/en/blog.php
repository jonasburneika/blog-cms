<?php

// Core-owned UI strings for blog:: package translations.
// Accessible as __('blog::blog.howto.default_title'), etc.
//
// Note: BlogCms\Support\SchemaBuilder does NOT currently reference these keys —
// the JSON-LD HowTo/FAQ schema falls back to raw post fields ($post->title,
// $post->excerpt), not translated strings, mirroring the source Blade's
// @push('schema') block exactly. These defaults are provided for core-owned
// admin/preview UI that renders a HowTo/FAQ section using the same fallback
// copy as the app's visible page (source: lang/en/frontend.php `howto`/`faq`
// keys), so apps don't have to redeclare them. The full frontend.php theme
// file stays in the app.

return [
    'howto' => [
        'default_title' => 'How To Guide',
        'tip' => 'Tip:',
    ],
    'faq' => [
        'default_title' => 'Frequently Asked Questions',
    ],
];
