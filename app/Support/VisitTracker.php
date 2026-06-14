<?php

namespace App\Support;

use App\Models\HomeVisit;
use App\Models\ShortLink;
use App\Models\ShortLinkClick;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class VisitTracker
{
    /** 1-year visitor cookie. */
    private const COOKIE = 'vid';
    private const TTL_MINUTES = 525600;

    /**
     * Resolve the visitor id from the cookie, issuing a new one if absent.
     *
     * @return array{0:string,1:bool} [visitorId, isNewBrowser]
     */
    public function resolve(Request $request): array
    {
        $vid = $request->cookie(self::COOKIE);

        if (! $vid || ! is_string($vid)) {
            $vid = (string) Str::uuid();
            // Queued cookie is attached to the response by the web middleware.
            Cookie::queue(Cookie::make(self::COOKIE, $vid, self::TTL_MINUTES));

            return [$vid, true];
        }

        return [$vid, false];
    }

    /** Record (or refresh) the visitor registry row. */
    public function touchVisitor(string $vid, Request $request): Visitor
    {
        $visitor = Visitor::firstOrNew(['visitor_id' => $vid]);

        if (! $visitor->exists) {
            $visitor->first_seen_at = now();
            $visitor->ip_hash = $this->ipHash($request);
            $visitor->user_agent = $this->ua($request);
        }

        $visitor->last_seen_at = now();
        $visitor->hits = (int) $visitor->hits + 1;
        $visitor->save();

        return $visitor;
    }

    /** Log a visit to the home page (counts unique vs repeat). */
    public function recordHomeVisit(Request $request): void
    {
        [$vid] = $this->resolve($request);
        $this->touchVisitor($vid, $request);

        $firstHomeHit = ! HomeVisit::where('visitor_id', $vid)->exists();

        HomeVisit::create([
            'visitor_id' => $vid,
            'is_unique' => $firstHomeHit,
            'ip_hash' => $this->ipHash($request),
            'user_agent' => $this->ua($request),
            'created_at' => now(),
        ]);
    }

    /** Log a click on a short link and bump its counters. */
    public function recordShortLinkClick(ShortLink $link, Request $request): void
    {
        [$vid] = $this->resolve($request);
        $this->touchVisitor($vid, $request);

        $firstForLink = ! ShortLinkClick::where('short_link_id', $link->id)
            ->where('visitor_id', $vid)->exists();

        ShortLinkClick::create([
            'short_link_id' => $link->id,
            'visitor_id' => $vid,
            'is_unique' => $firstForLink,
            'ip_hash' => $this->ipHash($request),
            'referer' => Str::limit((string) $request->headers->get('referer'), 250, ''),
            'user_agent' => $this->ua($request),
            'created_at' => now(),
        ]);

        $link->increment('clicks');
        if ($firstForLink) {
            $link->increment('unique_clicks');
        }
        $link->forceFill(['last_clicked_at' => now()])->save();
    }

    /**
     * Aggregated home-page traffic stats.
     *
     * @return array{total:int,unique:int,returning:int,repeated:int,today:int}
     */
    public function homeStats(): array
    {
        $total = (int) HomeVisit::count();
        $unique = (int) HomeVisit::distinct('visitor_id')->count('visitor_id');
        $returning = (int) HomeVisit::query()
            ->select('visitor_id')
            ->groupBy('visitor_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [
            'total' => $total,
            'unique' => $unique,
            'returning' => $returning,
            'repeated' => max(0, $total - $unique),
            'today' => (int) HomeVisit::whereDate('created_at', today())->count(),
        ];
    }

    private function ipHash(Request $request): string
    {
        return hash('sha256', $request->ip().'|'.config('app.key'));
    }

    private function ua(Request $request): ?string
    {
        return Str::limit((string) $request->userAgent(), 250, '') ?: null;
    }
}
