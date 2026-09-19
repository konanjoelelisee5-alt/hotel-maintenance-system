@props(['w', 'showDueDate' => false])

{{--
    Carte "ordre de travail" réutilisable : dashboard (file d'attente) et liste
    des OT sur mobile (remplace le tableau desktop, illisible en dessous de lg).
    Structure : titre+référence / lieu+assigné / badges statut+priorité / [échéance] / barre SLA.
--}}
<a href="{{ route('work-orders.show', $w) }}"
   class="block border-l-[3px] rounded-xl border border-line bg-white px-4 py-3.5 hover:bg-paper transition"
   style="border-left-color: {{ $w->priority->color }}">

    <div class="flex items-start justify-between gap-2">
        <span class="text-[15px] font-semibold text-navy leading-snug">{{ $w->title }}</span>
        <span class="font-mono text-[11.5px] text-[#26496B] whitespace-nowrap flex-shrink-0 mt-0.5">{{ $w->code() }}</span>
    </div>

    <div class="flex items-center justify-between gap-2 mt-1.5 text-[13px] text-[#6C6658]">
        <span class="truncate">Chambre {{ $w->room?->number ?? '—' }} · {{ $w->equipment?->name ?? $w->type->label }}</span>
        @if ($w->assignee)
            <span class="flex items-center gap-1.5 flex-shrink-0">
                <span class="w-5 h-5 rounded-full bg-gold flex items-center justify-center text-[9px] font-bold text-navy">{{ $w->assignee->initialsOrGenerated() }}</span>
                <span>{{ $w->assignee->name }}</span>
            </span>
        @else
            <span class="text-[#A09A8C] flex-shrink-0">— à affecter</span>
        @endif
    </div>

    <div class="flex items-center gap-1.5 mt-2.5">
        <x-work-order-status-badge :status="$w->status" />
        <x-work-order-priority-badge :priority="$w->priority" />
    </div>

    @if ($showDueDate && $w->sla_resolution_due_at)
        <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t border-line-soft text-[12px]">
            <span class="text-ink-grey">Échéance : {{ $w->sla_resolution_due_at->isToday() ? "aujourd'hui" : $w->sla_resolution_due_at->translatedFormat('d/m') }} {{ $w->sla_resolution_due_at->format('H:i') }}</span>
        </div>
    @endif

    <div class="flex items-center gap-2 mt-2.5">
        <span class="flex-1 h-[5px] rounded-full bg-line-soft overflow-hidden">
            <span class="block h-full {{ \App\Support\Swatch::bg($w->slaColorClass()) }}" style="width: {{ $w->slaProgressPercent() }}%"></span>
        </span>
        <span class="font-mono text-[11px] {{ \App\Support\Swatch::text($w->slaColorClass()) }} whitespace-nowrap">{{ $w->slaRemainingLabel() }}</span>
    </div>
</a>
