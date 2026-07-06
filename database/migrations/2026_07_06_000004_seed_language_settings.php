<?php

use BlogCms\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Seeds the language settings (default locale + enabled locales) from config,
 * so the Settings → Languages page has sensible initial values. Only sets keys
 * that aren't already present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Setting::get('default_locale') === null) {
            Setting::set('default_locale', config('blog-cms.default_locale', 'en'), 'languages');
        }

        if (Setting::get('locales') === null) {
            Setting::set('locales', (array) config('blog-cms.locales', ['en']), 'languages', 'json');
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', ['default_locale', 'locales'])->where('group', 'languages')->delete();
    }
};
