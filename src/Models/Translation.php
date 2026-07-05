<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = ['locale', 'group', 'key', 'value'];

    /**
     * Scope to filter by locale.
     */
    public function scopeForLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    /**
     * Scope to filter by group.
     */
    public function scopeForGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Get a single translation value from the database.
     */
    public static function getTranslation(string $locale, string $group, string $key): ?string
    {
        return static::where('locale', $locale)
            ->where('group', $group)
            ->where('key', $key)
            ->value('value');
    }

    /**
     * Set (create or update) a translation in the database.
     */
    public static function setTranslation(string $locale, string $group, string $key, ?string $value): self
    {
        return static::updateOrCreate(
            ['locale' => $locale, 'group' => $group, 'key' => $key],
            ['value' => $value]
        );
    }
}
