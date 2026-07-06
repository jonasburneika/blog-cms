<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records the previous URL of a post/category whenever its slug changes, so the
 * old link keeps working. `old_path` is the full locale-prefixed path as it was
 * (e.g. "lt/alus/senas-pavadinimas"); the target is resolved live at request
 * time, so a slug that moves A→B→C redirects straight to C (no chains).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_url_history', function (Blueprint $table) {
            $table->id();
            $table->string('old_path')->unique();
            $table->string('target_type', 16); // 'post' | 'category'
            $table->unsignedBigInteger('target_id');
            $table->string('locale', 10);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_url_history');
    }
};
