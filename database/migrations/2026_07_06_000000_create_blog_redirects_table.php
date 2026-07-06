<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_redirects', function (Blueprint $table) {
            $table->id();

            // Source page being redirected away from (a live post or category).
            $table->string('source_type', 16); // 'post' | 'category'
            $table->unsignedBigInteger('source_id');

            // Target page the visitor is sent to (a live post or category).
            $table->string('target_type', 16); // 'post' | 'category'
            $table->unsignedBigInteger('target_id');

            // 301 (permanent) or 302 (temporary).
            $table->unsignedSmallInteger('status_code')->default(301);

            $table->timestamps();

            // Only one redirect may own a given source page.
            $table->unique(['source_type', 'source_id']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_redirects');
    }
};
