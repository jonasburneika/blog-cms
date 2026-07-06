<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table-of-contents storage for posts. The JSON columns are maps keyed by
 * locale (e.g. {"en": [...], "lt": [...]}), populated by BlogPostTocObserver
 * on save — one TOC per language, since headings differ per translation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('toc_items')->nullable()->after('faq_title');          // {locale: [items]}
            $table->json('content_rendered')->nullable()->after('toc_items');   // {locale: html-with-ids}
            $table->json('toc_hash')->nullable()->after('content_rendered');    // {locale: sha256}
            $table->boolean('toc_enabled')->default(true)->after('toc_hash');
            $table->timestamp('toc_generated_at')->nullable()->after('toc_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['toc_items', 'content_rendered', 'toc_hash', 'toc_enabled', 'toc_generated_at']);
        });
    }
};
