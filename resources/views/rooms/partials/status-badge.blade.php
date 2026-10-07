{{-- Badge d'état commun aux lieux et aux équipements. --}}
@php
    $classes = match ($status) {
        'disponible', 'operationnel' => 'bg-ok-bg text-green',
        'occupee' => 'bg-info-bg text-blue',
        'maintenance' => 'bg-warn-bg text-warn-ink',
        default => 'bg-line text-ink-muted',
    };
@endphp
<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap {{ $classes }}">{{ $label }}</span>
