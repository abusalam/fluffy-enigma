<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force https links only when the configured APP_URL is https
        // (i.e. a real domain with TLS), not when serving plain HTTP on an IP.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Super administrators bypass every permission check.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Expose branding to every view without a controller.
        View::composer('*', function ($view) {
            $view->with('brandName', app_brand_name());
            $view->with('brandLogo', app_logo_url());
        });
    }
}
