{{-- Gestes du service demandeur sur sa propre demande (fiche OT de la réception) :
     ajouter une précision, retirer une erreur. Rien ne modifie l'OT lui-même.
     Le Housekeeping a les mêmes gestes dans sa fiche (housekeeping.partials.work-order-detail). --}}
@php
    $canComplement = auth()->user()->can('complement', $workOrder);
    $withdrawLeft = auth()->user()->can('withdraw', $workOrder)
        ? max(1, (int) ceil(now()->diffInMinutes($workOrder->created_at->copy()->addMinutes(\App\Support\Housekeeping::WITHDRAW_WINDOW_MINUTES), false)))
        : null;
@endphp

@if ($canComplement || $withdrawLeft)
    {{-- x-data : $dispatch n'existe que dans un composant Alpine. --}}
    <div x-data class="bg-white border border-line rounded-xl px-5 py-3.5 flex flex-wrap items-center gap-2.5">
        <span class="text-[13px] text-ink-muted mr-auto">Votre demande</span>
        @if ($canComplement)
            <button type="button" class="btn btn-secondary" @click="$dispatch('hk-complement-open')"><x-hk.icon name="plus" :size="16" /> Ajouter une précision</button>
        @endif
        @if ($withdrawLeft)
            <button type="button" class="btn btn-ghost !text-red" @click="$dispatch('hk-withdraw-open')"><x-hk.icon name="x" :size="16" /> Retirer</button>
            <span class="text-[12px] text-ink-grey">possible encore {{ $withdrawLeft }} min</span>
        @endif
    </div>

    @if ($withdrawLeft)
        @include('work-orders.partials.withdraw-form')
    @endif
    @if ($canComplement)
        @include('housekeeping.partials.complement-form')
    @endif
@endif
