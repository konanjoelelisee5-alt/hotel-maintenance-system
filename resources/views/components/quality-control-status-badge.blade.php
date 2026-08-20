@props(['status'])

@php
$colors = [
    'en_attente' => 'bg-gold-100 text-gold-700',
    'approuve'   => 'bg-emerald-100 text-emerald-700',
    'rejete'     => 'bg-red-100 text-red-700',
];

$labels = [
    'en_attente' => 'En attente',
    'approuve'   => 'Approuvé',
    'rejete'     => 'Rejeté',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . ($colors[$status] ?? 'bg-slate-100 text-slate-700')]) }}>
    {{ $labels[$status] ?? $status }}
</span>