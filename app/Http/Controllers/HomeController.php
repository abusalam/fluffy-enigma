<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\VisitTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * The site root. Every hit is counted (unique vs repeat visitors).
     * Before onboarding it renders the construction page; afterwards it
     * routes the visitor to the dashboard (authed) or login.
     */
    public function __invoke(Request $request, VisitTracker $tracker)
    {
        $tracker->recordHomeVisit($request);

        if (! Setting::isOnboarded()) {
            return response()->view('under-construction', status: 200);
        }

        return redirect()->route(Auth::check() ? 'dashboard' : 'login');
    }
}
