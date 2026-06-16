<div>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500">Home-page traffic and short-link activity at a glance.</p>
    </div>

    {{-- Home page traffic --}}
    <div class="card p-5">
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

    {{-- Short links --}}
    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-700">Short links</h3>
                <p class="text-xs text-gray-400">
                    {{ number_format($linkStats['links']) }} links ·
                    {{ number_format($linkStats['clicks']) }} clicks ·
                    {{ number_format($linkStats['unique']) }} unique
                </p>
            </div>
            @can('shortlinks.view')
                <a href="{{ route('shortlinks.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Manage →</a>
            @endcan
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Short link</th>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Destination</th>
                    <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Clicks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($topLinks as $link)
                    <tr>
                        <td class="px-5 py-3 font-mono text-sm font-medium text-brand-600">/{{ $link->code }}</td>
                        <td class="px-5 py-3 max-w-xs truncate text-sm text-gray-600" title="{{ $link->destination_url }}">{{ $link->destination_url }}</td>
                        <td class="px-5 py-3 text-sm text-gray-700">
                            <span class="font-semibold">{{ number_format($link->clicks) }}</span>
                            <span class="text-xs text-gray-400">({{ number_format($link->unique_clicks) }} unique)</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-sm text-gray-500">No short links yet. Create one from the Short Links page.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
