<div>
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex-1">
            <h2 class="text-xl font-semibold text-gray-900">Users</h2>
            <p class="text-sm text-gray-500">Manage accounts and assign roles.</p>
        </div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search name or email…" class="input max-w-xs">
        @if ($canManage)
            <button wire:click="create" class="btn-primary">New user</button>
        @endif
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Roles</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @foreach ($user->getRoleNames() as $role)
                                <span class="badge bg-brand-50 text-brand-700">{{ $role }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $user->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $user->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($canManage)
                                <button wire:click="edit({{ $user->id }})" class="font-medium text-brand-600 hover:text-brand-700">Edit</button>
                                <button wire:click="delete({{ $user->id }})" wire:confirm="Delete this user?" class="ml-3 font-medium text-red-600 hover:text-red-700">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    @if ($showModal)
        <x-modal :title="$editingId ? 'Edit user' : 'New user'">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label">Full name</label>
                    <input wire:model="name" type="text" class="input">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Email</label>
                    <input wire:model="email" type="email" class="input">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Password {{ $editingId ? '(leave blank to keep)' : '' }}</label>
                    <input wire:model="password" type="password" class="input">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Roles</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($allRoles as $role)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="roles" value="{{ $role }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                {{ $role }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Account active
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
