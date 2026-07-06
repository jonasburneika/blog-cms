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
            ->author(static::authorSchema($post));

        $coverUrl = static::coverUrl($post);

        if ($coverUrl) {
            $articleSchema->image($coverUrl);
        }

        if ($post->citations->count()) {
            $articleSchema->citation($post->citations->map(fn ($c) => Schema::webPage()
                ->setProperty('@type', $c->type ?: 'WebPage')
                ->setProperty('@id', $c->url)
                ->name($c->name)
            )->toArray());
        }

        if ($post->topics->count()) {
            $articleSchema->about($post->topics->map(function ($topic) {
                $thing = Schema::thing()
                    ->setProperty('@type', $topic->type ?: 'Thing')
                    ->name($topic->name);

                if ($topic->same_as) {
                    $thing->sameAs($topic->same_as);
                }

                return $thing;
            })->toArray());
        }

        if ($post->basedOn->count()) {
            $articleSchema->isBasedOn($post->basedOn->pluck('url')->toArray());
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
     * Author Person node, enriched from the author's profile when present
     * (job title, avatar, author-page url, social sameAs).
     */
    protected static function authorSchema(BlogPost $post): \Spatie\SchemaOrg\Person
    {
        $person = Schema::person()->name(static::authorName($post));

        $profile = $post->relationLoaded('authorProfile')
            ? $post->authorProfile
            : $post->authorProfile()->first();

        if (! $profile) {
            return $person;
        }

        if ($profile->display_name) {
            $person->name($profile->display_name);
        }
        if ($profile->role) {
            $person->jobTitle($profile->role);
        }
        if ($avatar = $profile->avatarUrl()) {
            $person->image($avatar);
        }
        if ($profile->is_indexable) {
            $person->url($profile->pageUrl());
        }
        if ($sameAs = array_column($profile->socialLinks(), 'url')) {
            $person->sameAs($sameAs);
        }

        return $person;
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
