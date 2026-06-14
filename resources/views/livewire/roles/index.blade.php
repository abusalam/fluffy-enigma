<div>
    <div class="mb-6 flex items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">Roles</h2>
            <p class="text-sm text-gray-500">Define roles and the permissions they grant.</p>
        </div>
        @if ($canManage)
            <button wire:click="create" class="btn-primary">New role</button>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($roles as $role)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $role->name }}</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }} ·
                            @if (in_array($role->name, $protectedRoles, true)) all permissions @else {{ $role->permissions_count }} permissions @endif
                        </p>
                    </div>
                    @if (in_array($role->name, $protectedRoles, true))
                        <span class="badge bg-amber-50 text-amber-700">protected</span>
                    @endif
                </div>
                @if ($canManage)
                    <div class="mt-4 flex gap-3 text-sm">
                        <button wire:click="edit({{ $role->id }})" class="font-medium text-brand-600 hover:text-brand-700">Edit</button>
                        @unless (in_array($role->name, $protectedRoles, true))
                            <button wire:click="delete({{ $role->id }})" wire:confirm="Delete this role?" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                        @endunless
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($showModal)
        <x-modal :title="$editingId ? 'Edit role' : 'New role'" maxWidth="max-w-2xl">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Role name</label>
                    <input wire:model="name" type="text" class="input" @disabled($editingId && in_array($name, $protectedRoles, true))>
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                @if (in_array($name, $protectedRoles, true))
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-700">The super-admin role implicitly holds every permission.</p>
                @else
                    <div>
                        <label class="label">Permissions</label>
                        <div class="grid max-h-64 grid-cols-1 gap-2 overflow-y-auto rounded-lg p-1 sm:grid-cols-2">
                            @foreach ($allPermissions as $perm)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" wire:model="permissions" value="{{ $perm }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span class="font-mono text-xs">{{ $perm }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
