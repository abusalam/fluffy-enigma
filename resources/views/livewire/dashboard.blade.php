<div wire:ignore.self>
    @php
        $abbr = function ($n) {
            $n = (float) $n;
            return match (true) {
                $n >= 1_000_000_000 => round($n / 1_000_000_000, 2).'B',
                $n >= 1_000_000 => round($n / 1_000_000, 2).'M',
                $n >= 1_000 => round($n / 1_000, 1).'K',
                default => (string) $n,
            };
        };
    @endphp

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Scheme Monitoring</h2>
        <p class="text-sm text-gray-500">Portfolio overview of welfare schemes, budgets and beneficiary coverage.</p>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <div class="text-sm text-gray-500">Total schemes</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($kpis['total']) }}</div>
            <div class="mt-1 text-xs text-green-600">{{ number_format($kpis['active']) }} active</div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-gray-500">Budget allocated</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $abbr($kpis['allocated']) }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $abbr($kpis['disbursed']) }} disbursed</div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-gray-500">Budget utilisation</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $kpis['utilisation'] }}%</div>
            <div class="mt-2 h-2 rounded-full bg-gray-100">
                <div class="h-2 rounded-full bg-brand-600" style="width: {{ min(100, $kpis['utilisation']) }}%"></div>
            </div>
        </div>
        <div class="card p-5">
            <div class="text-sm text-gray-500">Beneficiary coverage</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $kpis['coverage'] }}%</div>
            <div class="mt-1 text-xs text-gray-500">{{ $abbr($kpis['enrolled']) }} / {{ $abbr($kpis['target']) }}</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Schemes by status</h3>
            <canvas id="statusChart" height="220"></canvas>
        </div>
        <div class="card p-5">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Schemes by category</h3>
            <canvas id="categoryChart" height="220"></canvas>
        </div>
        <div class="card p-5 lg:col-span-1">
            <h3 class="mb-4 text-sm font-semibold text-gray-700">Budget: allocated vs disbursed (top 8, in millions)</h3>
            <canvas id="budgetChart" height="220"></canvas>
        </div>
    </div>

    {{-- Traffic: home page visitors + short-link clicks --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Home page traffic</h3>
                <span class="text-xs text-gray-400">{{ number_format($traffic['today']) }} today</span>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($traffic['total']) }}</div>
                    <div class="text-xs text-gray-500">Total hits</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-brand-600">{{ number_format($traffic['unique']) }}</div>
                    <div class="text-xs text-gray-500">Unique visitors</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ number_format($traffic['returning']) }}</div>
                    <div class="text-xs text-gray-500">Returning visitors</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($traffic['repeated']) }}</div>
                    <div class="text-xs text-gray-500">Repeat hits</div>
                </div>
            </div>
        </div>
        <div class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Short links</h3>
                @can('shortlinks.view')
                    <a href="{{ route('shortlinks.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Manage →</a>
                @endcan
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div><div class="text-xl font-bold text-gray-900">{{ number_format($linkStats['links']) }}</div><div class="text-xs text-gray-500">links</div></div>
                <div><div class="text-xl font-bold text-gray-900">{{ number_format($linkStats['clicks']) }}</div><div class="text-xs text-gray-500">clicks</div></div>
                <div><div class="text-xl font-bold text-gray-900">{{ number_format($linkStats['unique']) }}</div><div class="text-xs text-gray-500">unique</div></div>
            </div>
            @if ($topLinks->isNotEmpty())
                <ul class="mt-4 space-y-2 border-t border-gray-100 pt-3">
                    @foreach ($topLinks as $l)
                        <li class="flex items-center justify-between text-sm">
                            <span class="truncate font-mono text-xs text-gray-600">/{{ $l->code }}</span>
                            <span class="ml-2 shrink-0 font-semibold text-gray-900">{{ number_format($l->clicks) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Recent schemes --}}
    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-gray-700">Recently added schemes</h3>
            @can('schemes.view')
                <a href="{{ route('schemes.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all →</a>
            @endcan
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scheme</th>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Coverage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($recent as $scheme)
                    <tr>
                        <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $scheme->name }} <span class="text-gray-400">({{ $scheme->code }})</span></td>
                        <td class="px-5 py-3 text-sm text-gray-600">{{ $scheme->category }}</td>
                        <td class="px-5 py-3"><x-scheme-status :status="$scheme->status" /></td>
                        <td class="px-5 py-3 text-sm text-gray-600">{{ $scheme->enrollment_progress }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No schemes yet. Add one from the Schemes page.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
        <script>
            const charts = @json($charts);
            const palette = ['#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#65a30d'];

            function renderDashboardCharts() {
                if (typeof Chart === 'undefined') return;
                document.querySelectorAll('canvas').forEach(c => {
                    const existing = Chart.getChart(c);
                    if (existing) existing.destroy();
                });

                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: { labels: charts.status.labels, datasets: [{ data: charts.status.data, backgroundColor: palette }] },
                    options: { plugins: { legend: { position: 'bottom' } } }
                });

                new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: { labels: charts.category.labels, datasets: [{ label: 'Schemes', data: charts.category.data, backgroundColor: '#2563eb' }] },
                    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
                });

                new Chart(document.getElementById('budgetChart'), {
                    type: 'bar',
                    data: {
                        labels: charts.budget.labels,
                        datasets: [
                            { label: 'Allocated', data: charts.budget.allocated, backgroundColor: '#93c5fd' },
                            { label: 'Disbursed', data: charts.budget.disbursed, backgroundColor: '#2563eb' }
                        ]
                    },
                    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
                });
            }

            document.addEventListener('DOMContentLoaded', renderDashboardCharts);
            document.addEventListener('livewire:navigated', renderDashboardCharts);
        </script>
    @endpush
</div>
