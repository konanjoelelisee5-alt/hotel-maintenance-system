{{-- Badge d'état commun aux lieux et aux équipements. --}}
@php
    $classes = match ($status) {
        'disponible', 'operationnel' => 'bg-green-100 text-green-700',
        'occupee' => 'bg-blue-100 text-blue-700',
        'maintenance' => 'bg-orange-100 text-orange-700',
        default => 'bg-gray-200 text-gray-600',
    };
@endphp
<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap {{ $classes }}">{{ $label }}</span>
