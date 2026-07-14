<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency map for the AutoSEO importer: one row per remote entity, pointing
 * at the local model it was imported into. Keyed by (remote_type, remote_id) so
 * re-running the import updates the same local record instead of duplicating it.
 * `payload_hash` lets the importer skip unchanged remote records; `synced_at`
 * records the last successful sync. `local_type`/`local_id` are a morph-style
 * pointer to the local post/category/author/media model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_autoseo_map', function (Blueprint $table) {
            $table->id();
            $table->string('remote_type', 32);          // post | category | author | media
            $table->unsignedBigInteger('remote_id');
            $table->string('local_type', 191);
            $table->unsignedBigInteger('local_id');
            $table->string('payload_hash', 64)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['remote_type', 'remote_id']);
            $table->index(['local_type', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_autoseo_map');
    }
};
