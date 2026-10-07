{{-- Historique des changements de statut : frise chronologique, le plus récent en haut,
     statuts écrits en clair (badges) et motif cité quand il y en a un. --}}
<x-panel title="Historique" icon="history" collapsible :open="$workOrder->statusHistories->count() <= 6">
    <x-slot:badge>
        <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-ink-body">{{ $workOrder->statusHistories->count() }}</span>
    </x-slot:badge>

    @if ($workOrder->statusHistories->isEmpty())
        <p class="m-0 text-[13px] text-ink-grey">Aucun historique.</p>
    @else
        <ol class="m-0 p-0 list-none">
            @foreach ($workOrder->statusHistories->sortByDesc('id') as $history)
                <li class="relative flex gap-3 pb-4 last:pb-0">
                    @unless ($loop->last)
                        <span class="absolute left-[7px] top-4 bottom-0 w-px bg-line" aria-hidden="true"></span>
                    @endunless
                    <span class="relative z-10 mt-1 w-[15px] h-[15px] flex-shrink-0 rounded-full border-[3px] border-white ring-1 {{ $loop->first ? 'bg-gold ring-gold/40' : 'bg-navy ring-line' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-x-2 gap-y-1 flex-wrap text-[13px]">
                            <span class="font-semibold text-navy">{{ $history->changedBy?->name ?? 'Système' }}</span>
                            @if ($history->old_status)
                                <span class="text-ink-muted">a passé l'OT en</span>
                                <x-work-order-status-badge :status="$history->new_status" class="!py-0.5" />
                            @else
                                <span class="text-ink-muted">a créé l'OT</span>
                            @endif
                            <span class="ml-auto text-[11.5px] text-ink-grey whitespace-nowrap">{{ $history->created_at->format('d/m/Y H\hi') }}</span>
                        </div>
                        @if ($history->note)
                            <p class="m-0 mt-1.5 px-3 py-2 rounded-[8px] bg-paper border border-line-soft text-[12.5px] text-ink-body">{{ $history->note }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-panel>
