<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks author profiles created by the guest-blogging pipeline. The guest
 * submission flow only ever reuses/attributes to profiles flagged here, so a
 * guest post can never be attached to a pre-existing (possibly privileged)
 * account just because the unverified pitch email happens to match it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_author_profiles', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('blog_author_profiles', function (Blueprint $table) {
            $table->dropColumn('is_guest');
        });
    }
};
