@props(['room', 'tone' => 'navy', 'title' => null, 'workOrder' => null, 'stacked' => false])

{{-- Ligne « chambre » des pages de vente (chambres bloquées) : étiquette au numéro de
     la chambre, colorée selon l'état, titre + OT lié, lignes d'infos (slot meta),
     actions à droite (slot actions). tone : amber (à valider), red (hors vente),
     navy (à décider), gold (client concerné), grey (historique).
     stacked : boutons toujours sous le texte (colonnes étroites). --}}
@php
    $tile = [
        'amber' => 'bg-[#FBF1DF] text-[#7A5A16] border-amber/30',
        'red' => 'bg-[#FBE4E1] text-red border-red/25',
        'navy' => 'bg-[#EAF0F6] text-navy border-navy/15',
        'gold' => 'bg-[#FBF6EC] text-gold-700 border-gold/30',
        'grey' => 'bg-line-soft text-ink-grey border-line',
    ][$tone] ?? '';
    $isRoom = $room && ! $room->isCommonArea();
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 px-5 py-4 '.($stacked ? '' : 'sm:flex-row sm:items-center')]) }}>
    <div class="flex items-start gap-3.5 flex-1 min-w-0">
        <span class="w-[52px] h-[52px] flex-shrink-0 rounded-[12px] border flex flex-col items-center justify-center leading-none {{ $tile }}">
            <span class="text-[9.5px] font-semibold uppercase tracking-wide opacity-80">{{ $isRoom ? 'Ch.' : 'Lieu' }}</span>
            <span class="mt-0.5 font-mono text-[17px] font-bold">{{ $room?->number ?? '—' }}</span>
        </span>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[14.5px] font-semibold text-navy">{{ $room?->label ?? 'Lieu supprimé' }}</span>
                @if ($workOrder)
                    <a href="{{ route('work-orders.show', $workOrder) }}" class="font-mono text-[11.5px] text-[#4A4639] px-1.5 py-0.5 rounded-md bg-paper border border-line hover:border-navy/40 hover:text-navy">{{ $workOrder->code() }}</a>
                @endif
                {{ $badge ?? '' }}
            </div>
            @if ($title)
                <p class="m-0 mt-0.5 text-[13.5px] text-[#3d3a33] leading-snug">{{ $title }}</p>
            @endif
            @isset($meta)
                <div class="flex items-center gap-x-3 gap-y-1 flex-wrap mt-1.5 text-[12px] text-[#6C6658]">{{ $meta }}</div>
            @endisset
        </div>
    </div>
    @isset($actions)
        {{-- Téléphone : boutons alignés sous le texte (décalés de la largeur de l'étiquette). --}}
        <div class="flex flex-wrap items-center gap-2 pl-[66px] {{ $stacked ? '' : 'sm:pl-0 sm:justify-end sm:flex-shrink-0' }}">{{ $actions }}</div>
    @endisset
</div>
