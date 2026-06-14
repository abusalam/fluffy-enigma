<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ShortLinkController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Onboarding\Wizard;
use App\Livewire\Permissions\Index as PermissionsIndex;
use App\Livewire\Roles\Index as RolesIndex;
use App\Livewire\Schemes\Index as SchemesIndex;
use App\Livewire\ShortLinks\Index as ShortLinksIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| First-run onboarding (secret URL)
|--------------------------------------------------------------------------
| Reachable only while a valid {secret} segment matches config. The
| EnsureOnboarded middleware whitelists this path before setup completes.
*/
Route::get('/setup/{secret}', Wizard::class)->name('onboarding.setup');

/*
|--------------------------------------------------------------------------
| Guest / auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

// Root: counts the visit, then construction page / dashboard / login.
Route::get('/', HomeController::class)->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated application
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('permission:schemes.view')->name('dashboard');

    Route::get('/schemes', SchemesIndex::class)
        ->middleware('permission:schemes.view')->name('schemes.index');

    Route::get('/links', ShortLinksIndex::class)
        ->middleware('permission:shortlinks.view')->name('shortlinks.index');

    Route::get('/users', UsersIndex::class)
        ->middleware('permission:users.view')->name('users.index');

    Route::get('/roles', RolesIndex::class)
        ->middleware('permission:roles.view')->name('roles.index');

    Route::get('/permissions', PermissionsIndex::class)
        ->middleware('permission:permissions.view')->name('permissions.index');
});

/*
|--------------------------------------------------------------------------
| Public short-link redirect — root-level vanity URLs: /{code}
|--------------------------------------------------------------------------
| MUST be the last route: registration order gives every named route above
| precedence. The charset constraint matches only valid codes (no dots or
| slashes), so assets like /favicon.ico fall through to a normal 404. The
| ShortLinks module also forbids reserved words as codes.
*/
Route::get('/{code}', ShortLinkController::class)
    ->where('code', '[A-Za-z0-9][A-Za-z0-9_-]*')
    ->name('shortlink.redirect');
