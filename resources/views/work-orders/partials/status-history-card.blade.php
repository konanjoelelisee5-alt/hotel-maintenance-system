<x-accordion-card title="Historique">
    <ol class="space-y-4">
        @forelse ($workOrder->statusHistories as $history)
            <li class="relative pl-4 border-l-2 border-line text-sm">
                <span class="absolute -left-[5px] top-1 h-2 w-2 rounded-full bg-navy"></span>
                <p class="text-[#3d3a33]">
                    <span class="font-medium">{{ $history->changedBy?->name ?? 'Système' }}</span>
                    @if ($history->old_status)
                        a changé le statut en <strong>{{ $history->new_status }}</strong>
                    @else
                        a créé l'OT
                    @endif
                </p>
                <p class="text-xs text-ink-grey mt-0.5">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                @if ($history->note)
                    <p class="text-xs text-[#6C6658] italic mt-1">« {{ $history->note }} »</p>
                @endif
            </li>
        @empty
            <p class="text-sm text-ink-grey">Aucun historique.</p>
        @endforelse
    </ol>
</x-accordion-card>
