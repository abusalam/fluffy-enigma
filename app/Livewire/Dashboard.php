<?php

namespace App\Livewire;

use App\Models\Scheme;
use App\Models\ShortLink;
use App\Support\VisitTracker;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Scheme Monitoring')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        $statuses = Scheme::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        $byCategory = Scheme::select('category', DB::raw('count(*) as c'))
            ->groupBy('category')->orderByDesc('c')->pluck('c', 'category');

        $kpis = [
            'total' => (int) Scheme::count(),
            'active' => (int) ($statuses['active'] ?? 0),
            'allocated' => (float) Scheme::sum('budget_allocated'),
            'disbursed' => (float) Scheme::sum('budget_disbursed'),
            'target' => (int) Scheme::sum('target_beneficiaries'),
            'enrolled' => (int) Scheme::sum('enrolled_beneficiaries'),
        ];
        $kpis['utilisation'] = $kpis['allocated'] > 0
            ? round($kpis['disbursed'] / $kpis['allocated'] * 100, 1) : 0;
        $kpis['coverage'] = $kpis['target'] > 0
            ? round($kpis['enrolled'] / $kpis['target'] * 100, 1) : 0;

        // Top schemes by allocation for the budget bar chart.
        $topSchemes = Scheme::orderByDesc('budget_allocated')->limit(8)->get();

        $charts = [
            'status' => [
                'labels' => $statuses->keys()->map(fn ($s) => ucfirst($s))->all(),
                'data' => $statuses->values()->all(),
            ],
            'category' => [
                'labels' => $byCategory->keys()->all(),
                'data' => $byCategory->values()->all(),
            ],
            'budget' => [
                'labels' => $topSchemes->pluck('code')->all(),
                'allocated' => $topSchemes->map(fn ($s) => round($s->budget_allocated / 1_000_000, 2))->all(),
                'disbursed' => $topSchemes->map(fn ($s) => round($s->budget_disbursed / 1_000_000, 2))->all(),
            ],
        ];

        return view('livewire.dashboard', [
            'kpis' => $kpis,
            'charts' => $charts,
            'recent' => Scheme::latest()->limit(6)->get(),
            'traffic' => app(VisitTracker::class)->homeStats(),
            'linkStats' => [
                'links' => (int) ShortLink::visibleTo($user)->count(),
                'clicks' => (int) ShortLink::visibleTo($user)->sum('clicks'),
                'unique' => (int) ShortLink::visibleTo($user)->sum('unique_clicks'),
            ],
            'topLinks' => ShortLink::visibleTo($user)->orderByDesc('clicks')->limit(5)->get(),
        ]);
    }
}
