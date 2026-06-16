<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Settings</h2>
        <p class="text-sm text-gray-500">Update your portal name and logo. Changes apply across the site immediately.</p>
    </div>

    <form wire:submit="save" class="card space-y-6 p-6">
        {{-- Portal name --}}
        <div>
            <label class="label">Portal name</label>
            <input wire:model="appName" type="text" class="input" maxlength="120">
            @error('appName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Logo --}}
        <div>
            <label class="label">Logo</label>
            <div class="flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="New logo preview" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                @elseif ($currentLogo)
                    <img src="{{ asset('storage/'.$currentLogo) }}" alt="Current logo" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-600 text-3xl font-bold text-white">
                        {{ strtoupper(substr($appName ?: 'F', 0, 1)) }}
                    </div>
                @endif

                <div class="flex-1">
                    <input wire:model="logo" type="file" accept="image/*"
                           class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                    <div wire:loading wire:target="logo" class="mt-1 text-xs text-gray-500">Uploading…</div>
                    @if ($currentLogo)
                        <button type="button" wire:click="removeLogo" wire:confirm="Remove the current logo?"
                                class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Remove logo</button>
                    @endif
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-400">PNG, SVG, JPG or WebP · up to 2 MB. Leave blank to keep the current logo.</p>
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save,logo">
                <span wire:loading.remove wire:target="save">Save changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
