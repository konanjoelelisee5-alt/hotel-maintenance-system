{{-- Historique des contrôles qualité de l'OT. Le lancement d'un contrôle (et
     l'avertissement « vous avez réalisé l'intervention ») est dans le panneau
     « Pilotage », à l'étape « Résolu ». --}}
<x-panel title="Contrôles qualité" icon="shield" flush>
    <ul class="m-0 p-0 list-none divide-y divide-line-soft">
        @foreach ($workOrder->qualityControls as $qc)
            <li class="flex items-center justify-between gap-3 px-5 py-3 text-[13px]">
                <span class="min-w-0">
                    <span class="block font-semibold text-navy">{{ $qc->reviewer->name }}</span>
                    <span class="block text-[12px] text-ink-grey">{{ $qc->created_at->format('d/m/Y à H\hi') }}</span>
                </span>
                <span class="flex items-center gap-2.5 flex-shrink-0">
                    <x-quality-control-status-badge :status="$qc->status" />
                    <a href="{{ route('quality-controls.show', $qc) }}" class="btn btn-sm btn-secondary">Voir</a>
                </span>
            </li>
        @endforeach
    </ul>
</x-panel>
