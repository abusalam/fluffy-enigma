<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read a setting value (JSON-decoded), cached forever until changed.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $value = Cache::rememberForever("setting:{$key}", function () use ($key) {
                $row = static::query()->where('key', $key)->first();

                return $row ? $row->value : null;
            });
        } catch (\Throwable $e) {
            // Table may not exist yet (pre-migration request); fail safe.
            return $default;
        }

        if ($value === null) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Persist a setting value (JSON-encoded) and bust its cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value)],
        );

        Cache::forget("setting:{$key}");
    }

    /**
     * Has first-run onboarding been completed?
     */
    public static function isOnboarded(): bool
    {
        return (bool) static::get('onboarding_completed', false);
    }
}
