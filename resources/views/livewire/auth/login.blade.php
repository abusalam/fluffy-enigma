<div class="card p-8">
    <h2 class="mb-6 text-center text-lg font-semibold text-gray-900">Sign in to your account</h2>

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="label">Email address</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus class="input">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required class="input">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center">
            <input wire:model="remember" id="remember" type="checkbox" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
        </div>

        <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login">Signing in…</span>
        </button>
    </form>
</div>
