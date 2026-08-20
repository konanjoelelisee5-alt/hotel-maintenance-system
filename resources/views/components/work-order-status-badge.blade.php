@props(['status'])

@php
$colors = [
    'ouvert'     => 'bg-navy-100 text-navy-700',
    'en_cours'   => 'bg-gold-100 text-gold-700',
    'en_attente' => 'bg-orange-100 text-orange-700',
    'resolu'     => 'bg-emerald-100 text-emerald-700',
    'ferme'      => 'bg-slate-200 text-slate-600',
    'rejete'     => 'bg-red-100 text-red-700',
];

$labels = [
    'ouvert'     => 'Ouvert',
    'en_cours'   => 'En cours',
    'en_attente' => 'En attente',
    'resolu'     => 'Résolu',
    'ferme'      => 'Fermé',
    'rejete'     => 'Rejeté',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . ($colors[$status] ?? 'bg-slate-100 text-slate-700')]) }}>
    {{ $labels[$status] ?? $status }}
</span>