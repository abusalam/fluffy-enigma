<div class="card p-8">
    {{-- Step indicator --}}
    <div class="mb-8 flex items-center justify-between">
        @foreach (['Welcome', 'Branding', 'Super user', 'Finish'] as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="flex flex-1 items-center {{ ! $loop->last ? '' : 'flex-none' }}">
                <div class="flex flex-col items-center">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold
                        {{ $step > $n ? 'bg-brand-600 text-white' : ($step === $n ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'bg-gray-200 text-gray-500') }}">
                        {{ $step > $n ? '✓' : $n }}
                    </div>
                    <span class="mt-1 hidden text-xs text-gray-500 sm:block">{{ $label }}</span>
                </div>
                @unless ($loop->last)
                    <div class="mx-2 h-0.5 flex-1 {{ $step > $n ? 'bg-brand-600' : 'bg-gray-200' }}"></div>
                @endunless
            </div>
        @endforeach
    </div>

    {{-- Step 1: Welcome --}}
    @if ($step === 1)
        <div class="space-y-4 text-center">
            <h2 class="text-xl font-semibold text-gray-900">Let's set up your portal</h2>
            <p class="text-sm text-gray-600">
                This one-time wizard creates your <strong>super administrator</strong> account and
                configures branding. Once finished, the public "under construction" page is replaced
                by the live portal. This secret setup link stops working after completion.
            </p>
            <div class="pt-4">
                <button wire:click="next" class="btn-primary w-full">Get started</button>
            </div>
        </div>
    @endif

    {{-- Step 2: Branding --}}
    @if ($step === 2)
        <div class="space-y-5">
            <h2 class="text-xl font-semibold text-gray-900">Branding</h2>
            <div>
                <label class="label">Portal name</label>
                <input wire:model="appName" type="text" class="input" placeholder="e.g. State Welfare Portal">
                @error('appName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Logo <span class="font-normal text-gray-400">(optional, PNG/SVG/JPG, ≤2 MB)</span></label>
                <input wire:model="logo" type="file" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <div wire:loading wire:target="logo" class="mt-2 text-xs text-gray-500">Uploading…</div>
                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" class="mt-3 h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                @endif
            </div>
            <div class="flex gap-3 pt-2">
                <button wire:click="back" class="btn-secondary">Back</button>
                <button wire:click="next" class="btn-primary ml-auto">Continue</button>
            </div>
        </div>
    @endif

    {{-- Step 3: Super user --}}
    @if ($step === 3)
        <div class="space-y-5">
            <h2 class="text-xl font-semibold text-gray-900">Create super administrator</h2>
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
                <label class="label">Password</label>
                <input wire:model="password" type="password" class="input">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Confirm password</label>
                <input wire:model="password_confirmation" type="password" class="input">
            </div>
            <div class="flex gap-3 pt-2">
                <button wire:click="back" class="btn-secondary">Back</button>
                <button wire:click="next" class="btn-primary ml-auto">Continue</button>
            </div>
        </div>
    @endif

    {{-- Step 4: Finish --}}
    @if ($step === 4)
        <div class="space-y-5">
            <h2 class="text-xl font-semibold text-gray-900">Review &amp; finish</h2>
            <dl class="divide-y divide-gray-100 rounded-lg ring-1 ring-gray-200">
                <div class="flex justify-between px-4 py-3 text-sm"><dt class="text-gray-500">Portal name</dt><dd class="font-medium text-gray-900">{{ $appName }}</dd></div>
                <div class="flex justify-between px-4 py-3 text-sm"><dt class="text-gray-500">Administrator</dt><dd class="font-medium text-gray-900">{{ $name }}</dd></div>
                <div class="flex justify-between px-4 py-3 text-sm"><dt class="text-gray-500">Email</dt><dd class="font-medium text-gray-900">{{ $email }}</dd></div>
                <div class="flex justify-between px-4 py-3 text-sm"><dt class="text-gray-500">Logo</dt><dd class="font-medium text-gray-900">{{ $logo ? 'Provided' : 'Default initials' }}</dd></div>
            </dl>
            <p class="text-sm text-gray-600">After finishing you'll be signed in as the super administrator and the portal goes live.</p>
            <div class="flex gap-3 pt-2">
                <button wire:click="back" class="btn-secondary">Back</button>
                <button wire:click="finish" class="btn-primary ml-auto" wire:loading.attr="disabled" wire:target="finish">
                    <span wire:loading.remove wire:target="finish">Finish setup</span>
                    <span wire:loading wire:target="finish">Finishing…</span>
                </button>
            </div>
        </div>
    @endif
</div>
