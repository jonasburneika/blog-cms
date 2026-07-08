<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable rich-text (WYSIWYG) content blocks the theme renders by key — e.g.
 * homepage copy shown before and after the featured posts. Title + content are
 * translatable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // e.g. "homepage_before_featured"
            $table->string('name');            // admin label
            $table->json('title')->nullable(); // translatable optional heading
            $table->json('content')->nullable(); // translatable HTML
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_content_blocks');
    }
};
