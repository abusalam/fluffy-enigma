<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Read an application setting (cached). Pass an array as $key to set.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('app_logo_url')) {
    /**
     * Resolve the configured logo URL, or null when none uploaded.
     */
    function app_logo_url(): ?string
    {
        $path = Setting::get('logo_path');

        return $path ? asset('storage/'.$path) : null;
    }
}

if (! function_exists('app_brand_name')) {
    function app_brand_name(): string
    {
        return Setting::get('app_name', config('app.name', 'Scheme Monitor'));
    }
}
