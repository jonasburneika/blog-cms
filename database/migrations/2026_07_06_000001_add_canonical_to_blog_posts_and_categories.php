<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Optional canonical target (another live post or category). When null,
            // the page is its own canonical.
            $table->string('canonical_type', 16)->nullable()->after('meta_description'); // 'post' | 'category'
            $table->unsignedBigInteger('canonical_id')->nullable()->after('canonical_type');
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->string('canonical_type', 16)->nullable()->after('meta_title'); // 'post' | 'category'
            $table->unsignedBigInteger('canonical_id')->nullable()->after('canonical_type');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['canonical_type', 'canonical_id']);
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn(['canonical_type', 'canonical_id']);
        });
    }
};
