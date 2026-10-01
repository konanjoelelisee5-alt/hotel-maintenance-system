{{-- Historique des contrôles qualité de l'OT. Le lancement d'un contrôle (et
     l'avertissement « vous avez réalisé l'intervention ») est dans le panneau
     « Pilotage », à l'étape « Résolu ». --}}
<div class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">Contrôles qualité</h3>
    </div>

    <div class="p-5">
        @foreach ($workOrder->qualityControls as $qc)
            <div class="flex justify-between items-center {{ ! $loop->first ? 'border-t border-line-soft pt-3 mt-3' : '' }} text-sm">
                <span class="text-slate-600">{{ $qc->created_at->format('d/m/Y H:i') }} — {{ $qc->reviewer->name }}</span>
                <div class="flex items-center gap-3">
                    <x-quality-control-status-badge :status="$qc->status" />
                    <a href="{{ route('quality-controls.show', $qc) }}" class="text-navy-700 hover:underline font-medium">Voir</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
