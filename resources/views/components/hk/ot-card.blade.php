{{-- Carte OT des écrans Housekeeping (gabarit des cartes d'OT de l'admin) : lieu et
     référence, catégorie, statut, technicien. reporter : qui a signalé (gouvernante).
     active : fiche ouverte à côté. --}}
@props(['w', 'href', 'reporter' => false, 'active' => false])

@php
    $hk = \App\Support\Housekeeping::class;
    $category = $hk::category($w);
    $urgent = $w->priority?->code === 'urgente';
@endphp

<a href="{{ $href }}" @if ($active) aria-current="true" @endif
   {{ $attributes->class(['block min-w-0 rounded-xl border bg-white px-4 py-3.5 transition',
        'border-navy shadow-[inset_3px_0_0_#0E2136] bg-paper' => $active,
        'border-line hover:bg-paper' => ! $active]) }}>
    <div class="flex items-start justify-between gap-3">
        <span class="flex items-center gap-2.5 min-w-0">
            <span class="w-8 h-8 rounded-[8px] border flex items-center justify-center flex-shrink-0 {{ $urgent ? 'bg-hk-pending-bg border-hk-pending/20 text-hk-pending' : 'bg-paper border-line text-gold' }}">
                <x-hk.icon :name="$hk::categoryIcon($category)" :size="16" />
            </span>
            <span class="flex flex-col min-w-0">
                <span class="text-[14.5px] font-semibold text-navy leading-snug truncate">{{ $w->room?->label ?? 'Parties communes' }}</span>
                <span class="text-[12.5px] text-[#6C6658] truncate">{{ $category ? $hk::categoryLabel($category) : $w->title }}</span>
            </span>
        </span>
        <span class="font-mono text-[11.5px] text-[#26496B] whitespace-nowrap flex-shrink-0 mt-0.5">{{ $w->code() }}</span>
    </div>

    <div class="mt-2.5"><x-hk.status :status="$w->status" :urgent="$urgent" /></div>

    <div class="flex items-center justify-between gap-3 mt-2.5 pt-2.5 border-t border-line-soft text-[12.5px] text-[#6C6658]">
        @if ($w->status === 'annule')
            <span class="text-[#A09A8C] truncate">Annulé, aucune intervention</span>
        @elseif ($w->assignee)
            <span class="flex items-center gap-1.5 min-w-0">
                <span class="w-5 h-5 rounded-full bg-gold flex items-center justify-center text-[9px] font-bold text-navy flex-shrink-0">{{ $w->assignee->initialsOrGenerated() }}</span>
                <span class="truncate">{{ $w->assignee->name }}</span>
            </span>
        @else
            <span class="text-[#A09A8C] truncate">En attente d'un technicien</span>
        @endif
        <span class="flex items-center gap-1.5 flex-shrink-0 font-mono text-[11.5px] text-ink-grey">
            @if ($reporter && $w->reporter)<span class="font-sans text-[12px] truncate max-w-[110px]">{{ $w->reporter->name }} ·</span>@endif
            {{ $w->created_at->locale('fr')->diffForHumans(short: true) }}
        </span>
    </div>
</a>
