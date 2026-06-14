@props(['status'])

@php
    $map = [
        'active' => 'bg-green-50 text-green-700',
        'suspended' => 'bg-amber-50 text-amber-700',
        'closed' => 'bg-gray-100 text-gray-600',
        'draft' => 'bg-blue-50 text-blue-700',
    ];
    $class = $map[$status] ?? 'bg-gray-100 text-gray-600';
@endphp

<span class="badge {{ $class }}">{{ ucfirst($status) }}</span>
