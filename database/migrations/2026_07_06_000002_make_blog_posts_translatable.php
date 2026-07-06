<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts blog_posts content columns to spatie/laravel-translatable JSON.
 * Existing scalar values are wrapped under each row's source `locale` (kept as
 * an informational column). Idempotent per column.
 */
return new class extends Migration
{
    private array $columns = [
        'title', 'slug', 'excerpt', 'content',
        'meta_title', 'meta_description',
        'howto_title', 'howto_description', 'faq_title',
    ];

    /** Original types, for down(). */
    private array $types = [
        'title' => 'string', 'slug' => 'string',
        'excerpt' => 'text', 'content' => 'longText',
        'meta_title' => 'string', 'meta_description' => 'text',
        'howto_title' => 'string', 'howto_description' => 'text', 'faq_title' => 'string',
    ];

    public function up(): void
    {
        $default = config('blog-cms.default_locale', 'en');

        // 1. Wrap scalars into {locale: value} JSON while columns are still text.
        DB::table('blog_posts')->orderBy('id')->chunkById(500, function ($rows) use ($default) {
            foreach ($rows as $row) {
                $locale = $row->locale ?: $default;
                $update = [];
                foreach ($this->columns as $col) {
                    $val = $row->{$col};
                    if ($val === null || $this->looksLikeJsonObject($val)) {
                        continue;
                    }
                    $update[$col] = json_encode([$locale => $val], JSON_UNESCAPED_UNICODE);
                }
                if ($update) {
                    DB::table('blog_posts')->where('id', $row->id)->update($update);
                }
            }
        });

        // 2. Drop the single-slug unique index (slugs are now per-locale).
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        // 3. Flip column types to JSON.
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach ($this->columns as $col) {
                $table->json($col)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        $default = config('blog-cms.default_locale', 'en');

        // Restore scalar types, then unwrap the default-locale value.
        Schema::table('blog_posts', function (Blueprint $table) {
            foreach ($this->columns as $col) {
                $table->{$this->types[$col]}($col)->nullable()->change();
            }
        });

        DB::table('blog_posts')->orderBy('id')->chunkById(500, function ($rows) use ($default) {
            foreach ($rows as $row) {
                $update = [];
                foreach ($this->columns as $col) {
                    $decoded = json_decode((string) $row->{$col}, true);
                    if (is_array($decoded)) {
                        $update[$col] = $decoded[$row->locale ?? $default] ?? reset($decoded) ?: null;
                    }
                }
                if ($update) {
                    DB::table('blog_posts')->where('id', $row->id)->update($update);
                }
            }
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    private function looksLikeJsonObject(mixed $value): bool
    {
        return is_string($value)
            && str_starts_with(ltrim($value), '{')
            && is_array(json_decode($value, true));
    }
};
