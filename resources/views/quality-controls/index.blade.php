@php
    $user = auth()->user();
    $kpis = [
        ['label' => 'À contrôler', 'value' => $toReview->count() - $inProgress, 'sub' => 'réparations déclarées terminées', 'dot' => 'bg-blue'],
        ['label' => 'Contrôles en cours', 'value' => $inProgress, 'sub' => 'commencés, décision à prendre', 'dot' => 'bg-gold'],
        ['label' => 'Corrections en cours', 'value' => $corrections->count(), 'sub' => 'refusées, à reprendre', 'dot' => 'bg-red'],
        ['label' => 'Validés sur 7 jours', 'value' => $recent->where('status', 'approuve')->count(), 'sub' => 'OT fermés après contrôle', 'dot' => 'bg-green'],
    ];
@endphp

<x-app-layout crumb="Exploitation" page-title="Validation">
    {{-- Dernière étape du cycle d'un OT : le responsable vérifie la réparation avant
         de fermer l'OT. Celui qui a réalisé l'intervention ne la contrôle pas lui-même. --}}
    <x-kpi-band :items="$kpis" tiles />

    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-4 sm:px-6 pt-5 pb-3.5 border-b border-line">
            <h2 class="m-0 text-[17px] font-semibold text-navy">Réparations à contrôler</h2>
            <p class="m-0 mt-0.5 text-[12.5px] text-[#6C6658]">{{ $toReview->count() }} OT · la plus ancienne en premier</p>
        </div>

        @forelse ($toReview as $w)
            @php
                $pending = $w->qualityControls->firstWhere('status', 'en_attente');
                $canReview = $user->can('reviewQuality', $w);
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 sm:px-6 py-4 border-b border-line-soft last:border-b-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('work-orders.show', $w) }}#validation" class="font-mono text-[12.5px] text-[#4A4639] hover:text-navy">{{ $w->code() }}</a>
                        <a href="{{ route('work-orders.show', $w) }}#validation" class="text-[14.5px] font-semibold text-navy hover:underline">{{ $w->title }}</a>
                        <x-work-order-priority-badge :priority="$w->priority" />
                    </div>
                    <div class="mt-1 text-[13px] text-[#6C6658]">
                        {{ $w->room?->label ?? '—' }} · {{ $w->equipment?->name ?? $w->type->label }}
                        · réparé par <span class="font-medium text-[#4A4639]">{{ $w->assignee?->name ?? '—' }}</span>
                        @if ($w->completed_at) {{ $w->completed_at->locale('fr')->diffForHumans() }} @endif
                    </div>
                </div>
                <div class="flex-shrink-0">
                    @if (! $canReview)
                        <span class="text-[12.5px] text-amber font-medium">Vous l'avez réalisée : contrôle par un autre responsable</span>
                    @elseif ($pending)
                        <a href="{{ route('quality-controls.show', $pending) }}" class="btn btn-secondary w-full sm:w-auto"><x-nav-icon name="shield" /> Terminer le contrôle</a>
                    @else
                        <a href="{{ route('quality-controls.create', $w) }}" class="btn btn-primary w-full sm:w-auto"><x-nav-icon name="shield" /> Contrôler</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="px-5 py-11 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-[#E6F3EC] flex items-center justify-center text-green"><x-nav-icon name="check" class="w-5 h-5" /></div>
                <div class="text-[14px] font-semibold">Aucune réparation à contrôler</div>
                <div class="text-[12.5px] text-[#6C6658] max-w-[360px] leading-relaxed">Les OT déclarés « Résolu » par les techniciens apparaîtront ici.</div>
            </div>
        @endforelse
    </section>

    <div class="grid gap-5 lg:grid-cols-2 items-start">
        <x-panel title="Corrections en cours" icon="alert" flush>
            <x-slot:badge><span class="font-mono text-[12px] text-[#6C6658]">{{ $corrections->count() }}</span></x-slot:badge>
            @forelse ($corrections as $w)
                <a href="{{ route('work-orders.show', $w) }}#validation" class="flex items-start gap-3 px-5 py-3.5 border-b border-line-soft last:border-b-0 hover:bg-paper">
                    <span class="w-[7px] h-[7px] rounded-full bg-red mt-[7px] flex-shrink-0"></span>
                    <span class="flex flex-col gap-0.5 min-w-0">
                        <span class="text-[13.5px] font-semibold text-navy truncate">{{ $w->code() }} · {{ $w->title }}</span>
                        <span class="text-[12px] text-[#6C6658]">À reprendre par {{ $w->assignee?->name ?? '—' }} · {{ $w->correctionRequests->whereNull('resolved_at')->count() }} correction(s) ouverte(s)</span>
                    </span>
                </a>
            @empty
                <p class="m-0 px-5 py-4 text-[13px] text-[#6C6658]">Aucune correction en cours.</p>
            @endforelse
        </x-panel>

        <x-panel title="Décisions des 7 derniers jours" icon="history" flush>
            @forelse ($recent as $qc)
                <a href="{{ route('quality-controls.show', $qc) }}" class="flex items-start gap-3 px-5 py-3.5 border-b border-line-soft last:border-b-0 hover:bg-paper">
                    <span class="w-[7px] h-[7px] rounded-full {{ $qc->status === 'approuve' ? 'bg-green' : 'bg-red' }} mt-[7px] flex-shrink-0"></span>
                    <span class="flex flex-col gap-0.5 min-w-0">
                        <span class="text-[13.5px] font-semibold text-navy truncate">{{ $qc->workOrder->code() }} · {{ $qc->workOrder->title }}</span>
                        <span class="text-[12px] text-[#6C6658]">{{ $qc->status_label }} par {{ $qc->reviewer?->name ?? '—' }} · {{ $qc->reviewed_at->format('d/m à H\hi') }}</span>
                    </span>
                </a>
            @empty
                <p class="m-0 px-5 py-4 text-[13px] text-[#6C6658]">Aucune décision cette semaine.</p>
            @endforelse
        </x-panel>
    </div>
</x-app-layout>
