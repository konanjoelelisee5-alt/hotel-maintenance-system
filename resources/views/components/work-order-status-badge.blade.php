@props(['status'])

{{-- Libellé et couleur viennent du modèle (WorkOrder::STATUS_LABELS / STATUS_COLORS) :
     même couleur pour un statut partout dans l'application. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap '.\App\Support\Swatch::pill(\App\Models\WorkOrder::statusColor($status))]) }}>
    {{ \App\Models\WorkOrder::STATUS_LABELS[$status] ?? $status }}
</span>
