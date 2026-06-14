<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * Until first-run onboarding completes, the public sees the
     * "Under construction" page. The secret setup URL, Livewire's
     * update endpoint and static assets remain reachable so the
     * wizard itself can run.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::isOnboarded()) {
            return $next($request);
        }

        $allowed = $request->is(
            '/',        // HomeController renders the construction page + counts the hit
            'setup/*',
            'livewire/*',
            'build/*',
            'storage/*',
            'vendor/*',
            'up',
            'favicon.ico',
        );

        if ($allowed) {
            return $next($request);
        }

        return response()->view('under-construction', status: 200);
    }
}
