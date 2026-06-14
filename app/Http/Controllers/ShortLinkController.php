<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\VisitTracker;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    /** Public redirect endpoint: /s/{code} → destination, counting the hit. */
    public function __invoke(Request $request, VisitTracker $tracker, string $code)
    {
        $link = ShortLink::where('code', $code)->where('is_active', true)->first();

        abort_if($link === null, 404);

        $tracker->recordShortLinkClick($link, $request);

        return redirect()->away($link->destination_url, 302);
    }
}
