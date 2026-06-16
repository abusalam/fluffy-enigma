<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · {{ $brandName ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-gray-900 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-full">
    @php
        $nav = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'perm' => 'dashboard.view', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'shortlinks.index', 'label' => 'Short Links', 'perm' => 'shortlinks.view', 'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101M10.172 13.828a4 4 0 005.656 0l4-4a4 4 0 10-5.656-5.656l-1.1 1.1'],
            ['route' => 'users.index', 'label' => 'Users', 'perm' => 'users.view', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1a4 4 0 100-8 4 4 0 000 8z'],
            ['route' => 'roles.index', 'label' => 'Roles', 'perm' => 'roles.view', 'icon' => 'M12 11c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM12 11V7m0 4v4m6-9a9 9 0 11-12 0'],
            ['route' => 'permissions.index', 'label' => 'Permissions', 'perm' => 'permissions.view', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        ];
    @endphp

    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 transform bg-white shadow-lg transition-transform lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5">
            @if (! empty($brandLogo))
                <img src="{{ $brandLogo }}" alt="logo" class="h-8 w-auto">
            @else
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-sm font-bold text-white">
                    {{ strtoupper(substr($brandName ?? 'S', 0, 1)) }}
                </div>
            @endif
            <span class="truncate font-semibold">{{ $brandName ?? config('app.name') }}</span>
        </div>
        <nav class="space-y-1 px-3 py-4">
            @foreach ($nav as $item)
                @can($item['perm'])
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-brand-50 text-brand-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endcan
            @endforeach
        </nav>
    </aside>

    {{-- Main column --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="text-gray-500 lg:hidden" @click="sidebarOpen = true">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <h1 class="text-lg font-semibold text-gray-900">{{ $header ?? ($title ?? '') }}</h1>
            <div class="ml-auto flex items-center gap-4">
                <div class="hidden text-right sm:block">
                    <div class="text-sm font-medium text-gray-900">{{ auth()->user()?->name }}</div>
                    <div class="text-xs text-gray-500">{{ auth()->user()?->getRoleNames()->implode(', ') }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Sign out</button>
                </form>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">
                    {{ session('status') }}
                </div>
            @endif
            {{ $slot }}

            <footer class="mt-10 border-t border-gray-200 pt-4 text-center text-xs text-gray-400">
                {{ $brandName ?? config('app.name') }} ·
                <span class="font-mono">v{{ config('app.version') }}</span>
            </footer>
        </main>
    </div>
</div>
@livewireScripts
@stack('scripts')
</body>
</html>
