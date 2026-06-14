<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">Short links</h2>
            <p class="text-sm text-gray-500">Create short URLs and track how many times they're clicked.</p>
        </div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search…" class="input max-w-xs">
        @if ($canManage)
            <button wire:click="create" class="btn-primary">New link</button>
        @endif
    </div>

    {{-- Totals --}}
    <div class="mb-4 grid grid-cols-3 gap-4">
        <div class="card p-4"><div class="text-xs text-gray-500">Links</div><div class="text-xl font-bold text-gray-900">{{ number_format($totals['links']) }}</div></div>
        <div class="card p-4"><div class="text-xs text-gray-500">Total clicks</div><div class="text-xl font-bold text-gray-900">{{ number_format($totals['clicks']) }}</div></div>
        <div class="card p-4"><div class="text-xs text-gray-500">Unique clicks</div><div class="text-xl font-bold text-gray-900">{{ number_format($totals['unique']) }}</div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Short link</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Destination</th>
                    @if ($viewAll)<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Owner</th>@endif
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Clicks</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($links as $link)
                    <tr class="hover:bg-gray-50" wire:key="link-{{ $link->id }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <a href="{{ $link->short_url }}" target="_blank" class="font-mono text-sm font-medium text-brand-600 hover:text-brand-700">/{{ $link->code }}</a>
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $link->short_url }}'); copied = true; setTimeout(() => copied = false, 1200)"
                                        class="text-xs text-gray-400 hover:text-gray-600">
                                    <span x-show="!copied">copy</span><span x-show="copied" x-cloak class="text-green-600">copied!</span>
                                </button>
                            </div>
                            @if ($link->title)<div class="text-xs text-gray-400">{{ $link->title }}</div>@endif
                        </td>
                        <td class="px-4 py-3 max-w-xs truncate text-sm text-gray-600" title="{{ $link->destination_url }}">{{ $link->destination_url }}</td>
                        @if ($viewAll)<td class="px-4 py-3 text-sm text-gray-600">{{ $link->creator?->name ?? '—' }}</td>@endif
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <span class="font-semibold">{{ number_format($link->clicks) }}</span>
                            <span class="text-xs text-gray-400">({{ number_format($link->unique_clicks) }} unique)</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $link->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $link->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($canManage)
                                <button wire:click="toggle({{ $link->id }})" class="font-medium text-gray-500 hover:text-gray-700">{{ $link->is_active ? 'Disable' : 'Enable' }}</button>
                                <button wire:click="edit({{ $link->id }})" class="ml-3 font-medium text-brand-600 hover:text-brand-700">Edit</button>
                                <button wire:click="delete({{ $link->id }})" wire:confirm="Delete this short link?" class="ml-3 font-medium text-red-600 hover:text-red-700">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $viewAll ? 6 : 5 }}" class="px-4 py-8 text-center text-sm text-gray-500">No short links yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $links->links() }}</div>

    @if ($showModal)
        <x-modal :title="$editingId ? 'Edit short link' : 'New short link'">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Destination URL</label>
                    <input wire:model="destination_url" type="url" class="input" placeholder="https://example.com/very/long/path">
                    @error('destination_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Custom code <span class="font-normal text-gray-400">(optional — min 6 chars, leave blank to auto-generate)</span></label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-400">{{ url('/') }}/</span>
                        <input wire:model="code" type="text" class="input" placeholder="my-link">
                    </div>
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Title <span class="font-normal text-gray-400">(optional)</span></label>
                    <input wire:model="title" type="text" class="input">
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Active
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
