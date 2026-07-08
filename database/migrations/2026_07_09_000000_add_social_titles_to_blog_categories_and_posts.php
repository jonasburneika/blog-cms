<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Independent, per-locale Open Graph and Twitter/X card titles for categories
 * and posts. Stored as translatable JSON (like title/meta_title). Nullable —
 * the theme falls back to meta_title/title when a value is empty, so these are
 * pure overrides.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blog_categories', 'blog_posts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->json('og_title')->nullable()->after('meta_title');
                $t->json('twitter_title')->nullable()->after('og_title');
            });
        }
    }

    public function down(): void
    {
        foreach (['blog_categories', 'blog_posts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['og_title', 'twitter_title']);
            });
        }
    }
};
