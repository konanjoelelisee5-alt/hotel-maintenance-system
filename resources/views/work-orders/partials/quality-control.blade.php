<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-semibold text-navy-900 text-sm">Contrôle qualité</h3>
        @if ($workOrder->status === 'resolu')
            <a href="{{ route('quality-controls.create', $workOrder) }}"
               class="px-3 py-1.5 bg-navy-800 text-white text-xs font-medium rounded-md hover:bg-navy-900">
                Démarrer un contrôle qualité
            </a>
        @endif
    </div>

    <div class="p-5">
        @forelse ($workOrder->qualityControls as $qc)
            <div class="flex justify-between items-center {{ ! $loop->first ? 'border-t border-slate-100 pt-3 mt-3' : '' }} text-sm">
                <span class="text-slate-600">{{ $qc->created_at->format('d/m/Y H:i') }} — {{ $qc->reviewer->name }}</span>
                <div class="flex items-center gap-3">
                    <x-quality-control-status-badge :status="$qc->status" />
                    <a href="{{ route('quality-controls.show', $qc) }}" class="text-navy-700 hover:underline font-medium">Voir</a>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">Aucun contrôle qualité pour cet OT.</p>
        @endforelse
    </div>
</div>
