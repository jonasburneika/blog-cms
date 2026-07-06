<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Converts blog_categories content columns to spatie/laravel-translatable JSON.
 * Categories have no per-row locale, so existing values are wrapped under the
 * site default locale. Idempotent per column.
 */
return new class extends Migration
{
    private array $columns = ['title', 'menu_title', 'slug', 'description', 'meta_title'];

    private array $types = [
        'title' => 'string', 'menu_title' => 'string', 'slug' => 'string',
        'description' => 'text', 'meta_title' => 'string',
    ];

    public function up(): void
    {
        $default = config('blog-cms.default_locale', 'en');

        DB::table('blog_categories')->orderBy('id')->chunkById(500, function ($rows) use ($default) {
            foreach ($rows as $row) {
                $update = [];
                foreach ($this->columns as $col) {
                    $val = $row->{$col};
                    if ($val === null || $this->looksLikeJsonObject($val)) {
                        continue;
                    }
                    $update[$col] = json_encode([$default => $val], JSON_UNESCAPED_UNICODE);
                }
                if ($update) {
                    DB::table('blog_categories')->where('id', $row->id)->update($update);
                }
            }
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            foreach ($this->columns as $col) {
                $table->json($col)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        $default = config('blog-cms.default_locale', 'en');

        Schema::table('blog_categories', function (Blueprint $table) {
            foreach ($this->columns as $col) {
                $table->{$this->types[$col]}($col)->nullable()->change();
            }
        });

        DB::table('blog_categories')->orderBy('id')->chunkById(500, function ($rows) use ($default) {
            foreach ($rows as $row) {
                $update = [];
                foreach ($this->columns as $col) {
                    $decoded = json_decode((string) $row->{$col}, true);
                    if (is_array($decoded)) {
                        $update[$col] = $decoded[$default] ?? reset($decoded) ?: null;
                    }
                }
                if ($update) {
                    DB::table('blog_categories')->where('id', $row->id)->update($update);
                }
            }
        });

        Schema::table('blog_categories', function (Blueprint $table) {
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
