<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-navy-900 text-sm">Historique</h3>
    </div>

    <ol class="p-5 space-y-4">
        @forelse ($workOrder->statusHistories as $history)
            <li class="relative pl-4 border-l-2 border-slate-200 text-sm">
                <span class="absolute -left-[5px] top-1 h-2 w-2 rounded-full bg-navy-400"></span>
                <p class="text-slate-800">
                    <span class="font-medium">{{ $history->changedBy?->name ?? 'Système' }}</span>
                    @if ($history->old_status)
                        a changé le statut en <strong>{{ $history->new_status }}</strong>
                    @else
                        a créé l'OT
                    @endif
                </p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $history->created_at->format('d/m/Y H:i') }}</p>
                @if ($history->note)
                    <p class="text-xs text-slate-600 italic mt-1">« {{ $history->note }} »</p>
                @endif
            </li>
        @empty
            <p class="text-sm text-slate-500">Aucun historique.</p>
        @endforelse
    </ol>
</div>
