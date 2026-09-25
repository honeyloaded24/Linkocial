<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * In-memory static cache for the current request lifecycle.
     */
    protected static array $runtimeCache = [];

    /**
     * Retrieve a setting value with multi-tiered caching (memory + cache store).
     */
    public static function get($key, $default = null)
    {
        if (array_key_exists($key, self::$runtimeCache)) {
            $val = self::$runtimeCache[$key];
            return ($val !== null && $val !== '') ? $val : $default;
        }

        $value = Cache::remember("setting_{$key}", 3600, function () use ($key) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : null;
        });

        self::$runtimeCache[$key] = $value;
        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * Set a setting value and invalidate caches.
     */
    public static function set($key, $value)
    {
        Cache::forget("setting_{$key}");
        self::$runtimeCache[$key] = $value;
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Clear runtime memory cache.
     */
    public static function clearCache()
    {
        self::$runtimeCache = [];
    }
}
