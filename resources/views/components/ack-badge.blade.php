@props(['workOrder'])

{{-- OT affecté pas encore pris en charge (WorkOrder::awaitsAcknowledgement) : « Nouveau »
     pour son technicien, « Pas encore vu » pour les autres (le manager sait qui relancer). --}}
@if ($workOrder->awaitsAcknowledgement())
    @if ($workOrder->assigned_to === auth()->id())
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11.5px] font-semibold whitespace-nowrap bg-gold text-navy']) }}>Nouveau</span>
    @else
        <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11.5px] font-semibold whitespace-nowrap bg-warn-bg text-warn-ink']) }}>
            <x-nav-icon name="clock" class="w-3 h-3" /> Pas encore vu
        </span>
    @endif
@endif
