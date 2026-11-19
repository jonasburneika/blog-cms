<?php

namespace BlogCms\Support;

use Illuminate\Http\Request;

/**
 * The back-office URL prefix. Defaults to "admin"; sites can hide it behind a
 * non-guessable segment with BLOG_CMS_ADMIN_PATH (config blog-cms.admin.path).
 */
final class AdminPath
{
    public static function prefix(): string
    {
        return trim((string) config('blog-cms.admin.path', 'admin'), '/') ?: 'admin';
    }

    /** Absolute URL inside the back-office, e.g. url('posts/5/hooks'). */
    public static function url(string $path = ''): string
    {
        return url(self::prefix().($path !== '' ? '/'.ltrim($path, '/') : ''));
    }

    public static function is(Request $request): bool
    {
        return $request->is(self::prefix(), self::prefix().'/*');
    }
}
