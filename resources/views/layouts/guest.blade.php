<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign in' }} · {{ $brandName ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-gray-900 antialiased">
    <div class="flex min-h-full flex-col justify-center px-6 py-12">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 flex flex-col items-center">
                @if (! empty($brandLogo))
                    <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-20 w-20 rounded-full object-cover ring-1 ring-gray-200">
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-600 text-3xl font-bold text-white">
                        {{ strtoupper(substr($brandName ?? 'F', 0, 1)) }}
                    </div>
                @endif
                <h1 class="mt-4 text-xl font-semibold text-gray-900">{{ $brandName ?? config('app.name') }}</h1>
            </div>

            {{ $slot }}
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
