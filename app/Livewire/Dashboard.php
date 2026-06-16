<?php

namespace App\Livewire;

use App\Models\ShortLink;
use App\Support\VisitTracker;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard', [
            'traffic' => app(VisitTracker::class)->homeStats(),
            'linkStats' => [
                'links' => (int) ShortLink::visibleTo($user)->count(),
                'clicks' => (int) ShortLink::visibleTo($user)->sum('clicks'),
                'unique' => (int) ShortLink::visibleTo($user)->sum('unique_clicks'),
            ],
            'topLinks' => ShortLink::visibleTo($user)->orderByDesc('clicks')->limit(8)->get(),
        ]);
    }
}
