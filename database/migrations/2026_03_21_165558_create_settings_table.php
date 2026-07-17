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
        // Table name is configurable so apps whose 'settings' table is taken
        // (e.g. spatie/laravel-settings) can point the package elsewhere.
        Schema::create(config('blog-cms.tables.settings', 'settings'), function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('blog-cms.tables.settings', 'settings'));
    }
};
