<div class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft flex items-center justify-between">
        <h3 class="font-semibold text-navy-900 text-sm">Contrôle qualité</h3>
        @if ($workOrder->status === 'resolu')
            @can('reviewQuality', $workOrder)
                <a href="{{ route('quality-controls.create', $workOrder) }}"
                   class="px-3 py-1.5 bg-navy-800 text-white text-xs font-medium rounded-md hover:bg-navy-900">
                    Démarrer un contrôle qualité
                </a>
            @endcan
        @endif
    </div>

    @if ($workOrder->status === 'resolu' && $workOrder->wasExecutedBy(auth()->user()))
        {{-- Séparation des tâches : celui qui a réparé ne valide pas son propre travail. --}}
        <div class="mx-5 mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md text-xs text-amber-900">
            Vous avez réalisé cette intervention : le contrôle qualité doit être fait par un autre administrateur ou manager.
        </div>
    @endif

    <div class="p-5">
        @forelse ($workOrder->qualityControls as $qc)
            <div class="flex justify-between items-center {{ ! $loop->first ? 'border-t border-line-soft pt-3 mt-3' : '' }} text-sm">
                <span class="text-slate-600">{{ $qc->created_at->format('d/m/Y H:i') }} — {{ $qc->reviewer->name }}</span>
                <div class="flex items-center gap-3">
                    <x-quality-control-status-badge :status="$qc->status" />
                    <a href="{{ route('quality-controls.show', $qc) }}" class="text-navy-700 hover:underline font-medium">Voir</a>
                </div>
            </div>
        @empty
            <p class="text-sm text-ink-grey">Aucun contrôle qualité pour cet OT.</p>
        @endforelse
    </div>
</div>
