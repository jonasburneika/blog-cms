<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Like counter for posts, surfaced as schema.org UserLikes
 * (interactionStatistic / InteractionCounter) in the Article JSON-LD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('views_count');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
