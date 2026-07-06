<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Author profile data owned by the package (the app owns the users table, so we
 * don't touch it). One profile per user: byline/role, translatable bio, social
 * links, an author-page slug, and an indexable flag (thin author pages should
 * stay noindex). The avatar is a MediaLibrary collection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('display_name')->nullable();
            $table->string('role')->nullable();
            $table->json('bio')->nullable();          // translatable {locale: text}
            $table->json('social_links')->nullable();  // [{label,url}, …]
            $table->boolean('is_indexable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_author_profiles');
    }
};
