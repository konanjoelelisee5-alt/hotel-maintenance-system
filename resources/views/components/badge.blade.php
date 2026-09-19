@props(['color' => 'grey'])

{{--
    Badge pilule générique à couleur sémantique (App\Support\Swatch).
    Pour les statuts/priorités d'OT, préférer <x-work-order-status-badge> /
    <x-work-order-priority-badge> qui ont leur propre mapping libellé→couleur.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ' . \App\Support\Swatch::soft($color) . ' ' . \App\Support\Swatch::text($color)]) }}>
    {{ $slot }}
</span>
