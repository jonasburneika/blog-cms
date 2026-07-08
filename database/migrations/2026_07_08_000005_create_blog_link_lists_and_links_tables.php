<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manageable link lists (e.g. a "footer" list) and their links. Each link has a
 * translatable anchor label and points at a blog post, a blog category, or an
 * external URL, with a chosen target (_self/_blank/…) and optional rel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_link_lists', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // e.g. "footer" — how the theme fetches it
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_link_list_id')->constrained('blog_link_lists')->cascadeOnDelete();
            $table->json('label');                              // translatable anchor text
            $table->string('link_type')->nullable();            // post|category (null → external)
            $table->unsignedBigInteger('link_id')->nullable();  // target post/category id
            $table->string('url')->nullable();                  // external URL
            $table->string('target', 20)->default('_self');     // _self|_blank|_parent|_top
            $table->string('rel')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['blog_link_list_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_links');
        Schema::dropIfExists('blog_link_lists');
    }
};
