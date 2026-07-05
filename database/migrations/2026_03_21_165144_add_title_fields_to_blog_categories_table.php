<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->string('menu_title')->nullable()->after('name');
            $table->string('meta_title')->nullable()->after('description');
            $table->string('title')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn(['menu_title', 'title', 'meta_title']);
        });
    }
};
