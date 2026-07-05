<?php

namespace BlogCms\Support;

use BlogCms\Models\BlogPost;
use Spatie\SchemaOrg\Article;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\HowTo;
use Spatie\SchemaOrg\Schema;

/**
 * Builds Spatie\SchemaOrg JSON-LD graphs for a blog post.
 *
 * Ported from the app's resources/views/frontend/post.blade.php @push('schema')
 * block. Kept as plain builder methods (rather than baking the logic into the
 * Blade components) so apps can also call these directly if they need the
 * schema objects for something other than an inline <script> tag.
 */
class SchemaBuilder
{
    public static function article(BlogPost $post): Article
    {
        $articleSchema = Schema::article()
            ->headline($post->title)
            ->description($post->meta_description ?: $post->excerpt)
            ->datePublished($post->published_at?->toIso8601String())
            ->dateModified($post->updated_at->toIso8601String())
            ->author(Schema::person()->name(static::authorName($post)));

        $coverUrl = static::coverUrl($post);

        if ($coverUrl) {
            $articleSchema->image($coverUrl);
        }

        return $articleSchema;
    }

    public static function faqPage(BlogPost $post): ?FAQPage
    {
        if (! $post->faqs->count()) {
            return null;
        }

        $faqItems = $post->faqs->map(fn ($faq) => Schema::question()
            ->name($faq->question)
            ->acceptedAnswer(Schema::answer()->text($faq->answer))
        )->toArray();

        return Schema::fAQPage()->mainEntity($faqItems);
    }

    public static function howTo(BlogPost $post): ?HowTo
    {
        if (! $post->howToSteps->count()) {
            return null;
        }

        $howToSteps = $post->howToSteps->map(fn ($step) => Schema::howToStep()
            ->name($step->title)
            ->text($step->content)
        )->toArray();

        return Schema::howTo()
            ->name($post->howto_title ?: $post->title)
            ->description($post->howto_description ?: $post->excerpt)
            ->step($howToSteps);
    }

    /**
     * Author name for the Article's Person node.
     *
     * Source hardcoded a brand-specific fallback string; core falls back to
     * the app's own name via config('app.name') instead, so it stays neutral
     * across any theme that consumes this package.
     */
    protected static function authorName(BlogPost $post): string
    {
        return $post->author?->name ?: config('app.name');
    }

    /**
     * Cover image URL for the Article's image field.
     *
     * Source used $post->getFirstMediaUrl('cover_desktop', 'preview') directly
     * in the Blade view (not a theme helper) — InteractsWithMedia is native to
     * BlogCms\Models\BlogPost, so this works unchanged in core. Falls back to
     * a plain 'featured_image' column in case a post has no media library
     * upload but does have a legacy/manual image URL set.
     */
    public static function coverUrl(BlogPost $post): ?string
    {
        return $post->getFirstMediaUrl('cover_desktop', 'preview')
            ?: $post->getFirstMediaUrl('cover_desktop')
            ?: $post->featured_image
            ?: null;
    }
}
