<?php

namespace BlogCms\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    public function getTable(): string
    {
        return config('blog-cms.tables.settings', 'settings');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;

        return match($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            'encrypted' => $setting->value ? decrypt($setting->value) : $default,
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'string'): static
    {
        $storeValue = match($type) {
            'json' => json_encode($value),
            'encrypted' => $value ? encrypt($value) : null,
            'boolean' => $value ? '1' : '0',
            default => $value,
        };

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue, 'group' => $group, 'type' => $type]
        );
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)->pluck('value', 'key')->toArray();
    }
}
