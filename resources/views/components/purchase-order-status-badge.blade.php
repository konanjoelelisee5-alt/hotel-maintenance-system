@props(['status'])

{{-- Statut d'un bon de commande, couleurs de l'application (App\Support\Swatch). --}}
@php
    [$label, $color] = [
        'brouillon' => ['Brouillon', 'grey'],
        'envoyee' => ['Envoyée', 'blue'],
        'confirmee' => ['Confirmée', 'gold'],
        'reception_partielle' => ['Réception partielle', 'amber'],
        'receptionnee' => ['Réceptionnée', 'green'],
        'facturee' => ['Facturée', 'navy'],
        'annulee' => ['Annulée', 'muted'],
    ][$status] ?? [$status, 'grey'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap '.\App\Support\Swatch::pill($color)]) }}>
    {{ $label }}
</span>
