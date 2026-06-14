<div>
    <div class="mb-6 flex items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">Permissions</h2>
            <p class="text-sm text-gray-500">Atomic abilities that roles can grant. Used in route &amp; UI guards.</p>
        </div>
        @if ($canManage)
            <button wire:click="create" class="btn-primary">New permission</button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Used by roles</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($permissions as $perm)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-sm text-gray-900">{{ $perm->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $perm->roles_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($canManage)
                                <button wire:click="delete({{ $perm->id }})" wire:confirm="Delete this permission? Roles will lose it." class="text-sm font-medium text-red-600 hover:text-red-700">Delete</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($showModal)
        <x-modal title="New permission">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Permission name</label>
                    <input wire:model="name" type="text" class="input" placeholder="e.g. reports.export">
                    <p class="mt-1 text-xs text-gray-400">Lowercase, dot/dash/underscore only (e.g. <code>schemes.export</code>).</p>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Create</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
