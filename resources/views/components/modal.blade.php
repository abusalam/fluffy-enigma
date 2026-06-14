@props(['title' => '', 'maxWidth' => 'max-w-lg'])

<div class="fixed inset-0 z-50 overflow-y-auto" wire:keydown.escape="$set('showModal', false)">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/50" wire:click="$set('showModal', false)"></div>
        <div {{ $attributes->merge(['class' => "relative w-full $maxWidth card p-6"]) }}>
            @if ($title)
                <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>
