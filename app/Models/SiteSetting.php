<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'type', 'order'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null): ?string
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): bool
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("site_setting_{$key}");

        return $setting->wasRecentlyCreated || $setting->wasChanged();
    }

    /**
     * Get all settings as key-value array
     */
    public static function allAsArray(): array
    {
        return Cache::remember('site_settings_all', 3600, function () {
            return self::orderBy('order')->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $keys = self::pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget("site_setting_{$key}");
        }
        Cache::forget('site_settings_all');
    }

    /**
     * Parse template with booking data
     */
    public static function parseTemplate(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        return $template;
    }
}
