@props(['status'])

@php
$colors = [
    'en_attente' => 'bg-gold-100 text-gold-700',
    'approuve'   => 'bg-ok-bg text-green',
    'rejete'     => 'bg-danger-bg text-danger-ink',
];

$labels = [
    'en_attente' => 'En attente',
    'approuve'   => 'Approuvé',
    'rejete'     => 'Rejeté',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . ($colors[$status] ?? 'bg-line-soft text-ink-body')]) }}>
    {{ $labels[$status] ?? $status }}
</span>