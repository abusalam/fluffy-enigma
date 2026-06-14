<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">Schemes</h2>
            <p class="text-sm text-gray-500">Create and monitor welfare schemes.</p>
        </div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search…" class="input max-w-xs">
        <select wire:model.live="status" class="input max-w-[10rem]">
            <option value="">All statuses</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        @if ($canManage)
            <button wire:click="create" class="btn-primary">New scheme</button>
        @endif
    </div>

    <div class="card overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scheme</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Coverage</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Budget used</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($schemes as $scheme)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">{{ $scheme->name }}</div>
                            <div class="text-xs text-gray-400">{{ $scheme->code }} · {{ $scheme->department }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $scheme->category }}</td>
                        <td class="px-4 py-3"><x-scheme-status :status="$scheme->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-20 rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-brand-600" style="width: {{ min(100, $scheme->enrollment_progress) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $scheme->enrollment_progress }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $scheme->budget_utilisation }}%</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($canManage)
                                <button wire:click="edit({{ $scheme->id }})" class="font-medium text-brand-600 hover:text-brand-700">Edit</button>
                                <button wire:click="delete({{ $scheme->id }})" wire:confirm="Delete this scheme?" class="ml-3 font-medium text-red-600 hover:text-red-700">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No schemes match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $schemes->links() }}</div>

    @if ($showModal)
        <x-modal :title="$editingId ? 'Edit scheme' : 'New scheme'" maxWidth="max-w-2xl">
            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">Scheme name</label>
                    <input wire:model="name" type="text" class="input">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Code</label>
                    <input wire:model="code" type="text" class="input" placeholder="NHA-2024">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Department</label>
                    <input wire:model="department" type="text" class="input">
                    @error('department') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Category</label>
                    <select wire:model="category" class="input">
                        <option value="">—</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Status</label>
                    <select wire:model="scheme_status" class="input">
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    @error('scheme_status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Start date</label>
                    <input wire:model="start_date" type="date" class="input">
                </div>
                <div>
                    <label class="label">End date</label>
                    <input wire:model="end_date" type="date" class="input">
                    @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Budget allocated</label>
                    <input wire:model="budget_allocated" type="number" step="0.01" min="0" class="input">
                </div>
                <div>
                    <label class="label">Budget disbursed</label>
                    <input wire:model="budget_disbursed" type="number" step="0.01" min="0" class="input">
                    @error('budget_disbursed') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Target beneficiaries</label>
                    <input wire:model="target_beneficiaries" type="number" min="0" class="input">
                </div>
                <div>
                    <label class="label">Enrolled beneficiaries</label>
                    <input wire:model="enrolled_beneficiaries" type="number" min="0" class="input">
                    @error('enrolled_beneficiaries') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Description</label>
                    <textarea wire:model="description" rows="3" class="input"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2 sm:col-span-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
