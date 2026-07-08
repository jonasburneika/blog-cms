<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E-E-A-T fields for an author, feeding the schema.org Person node:
 *  - knows_about  → Person.knowsAbout  (areas of subject-matter expertise)
 *  - credentials  → Person.hasCredential (EducationalOccupationalCredential:
 *                   degrees, certifications, licenses — name + category)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_author_profiles', function (Blueprint $table) {
            $table->json('knows_about')->nullable()->after('social_links'); // ["topic", …]
            $table->json('credentials')->nullable()->after('knows_about');   // [{name, category}, …]
        });
    }

    public function down(): void
    {
        Schema::table('blog_author_profiles', function (Blueprint $table) {
            $table->dropColumn(['knows_about', 'credentials']);
        });
    }
};
