<?php

namespace BlogCms\Support;

use BlogCms\Models\Setting;

/**
 * Which modern image formats to generate + serve for content images. The admin
 * "Image Optimization" settings (an `image_formats` JSON array) take precedence
 * over config('blog-cms.media.content_image.formats'). Order is preference order
 * for <picture> — AVIF before WebP — so browsers pick the best they support.
 */
class ImageFormats
{
    /** Supported formats in <picture> preference order (best first). */
    public const SUPPORTED = ['avif', 'webp'];

    /** Enabled formats, filtered + ordered by preference. */
    public static function enabled(): array
    {
        $configured = Setting::get('image_formats');
        if (! is_array($configured)) {
            $configured = (array) config('blog-cms.media.content_image.formats', []);
        }

        $configured = array_map('strtolower', array_map('strval', $configured));

        // Intersect with SUPPORTED, preserving preference order.
        return array_values(array_filter(self::SUPPORTED, fn ($f) => in_array($f, $configured, true)));
    }
}
