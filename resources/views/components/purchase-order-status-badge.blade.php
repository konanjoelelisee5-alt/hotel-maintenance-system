@props(['status'])

@php
$colors = [
    'brouillon'           => 'bg-slate-100 text-slate-600',
    'envoyee'             => 'bg-navy-100 text-navy-700',
    'confirmee'           => 'bg-gold-100 text-gold-700',
    'reception_partielle' => 'bg-orange-100 text-orange-700',
    'receptionnee'        => 'bg-emerald-100 text-emerald-700',
    'facturee'            => 'bg-purple-100 text-purple-700',
    'annulee'             => 'bg-red-100 text-red-700',
];

$labels = [
    'brouillon'           => 'Brouillon',
    'envoyee'             => 'Envoyée',
    'confirmee'           => 'Confirmée',
    'reception_partielle' => 'Réception partielle',
    'receptionnee'        => 'Réceptionnée',
    'facturee'            => 'Facturée',
    'annulee'             => 'Annulée',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . ($colors[$status] ?? 'bg-slate-100 text-slate-700')]) }}>
    {{ $labels[$status] ?? $status }}
</span>