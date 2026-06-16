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
        return Setting::get('app_name', config('app.name', 'Fluffy Enigma'));
    }
}

if (! function_exists('landing_route')) {
    /**
     * The best landing route for the current user: the first section they have
     * permission to see. Keeps users without `dashboard.view` off the dashboard.
     */
    function landing_route(): string
    {
        $user = auth()->user();

        if (! $user) {
            return 'login';
        }

        $candidates = [
            'dashboard' => 'dashboard.view',
            'shortlinks.index' => 'shortlinks.view',
            'users.index' => 'users.view',
            'roles.index' => 'roles.view',
            'permissions.index' => 'permissions.view',
        ];

        foreach ($candidates as $route => $permission) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return 'dashboard';
    }
}
