<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simple standalone content pages (Terms, Privacy, Cookie Policy, About, …),
 * authored in the admin WYSIWYG. Title/slug/content/meta are translatable; the
 * app owns the public route and renders the content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('status')->default('draft'); // draft|published
            $table->boolean('is_indexable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_pages');
    }
};
