{{-- Réparations déjà faites au même endroit (WorkOrder::previousRepairs) : la panne
     revient-elle, qu'a-t-on fait la dernière fois ? Lien vers la fiche seulement si
     l'utilisateur peut l'ouvrir (un technicien ne voit que ses propres OT). --}}
@php
    $previous = $workOrder->previousRepairs();
    $place = $workOrder->equipment ? 'cet équipement' : 'ce lieu';
@endphp

<x-panel id="previous-repairs" title="Déjà réparé ici" icon="history" flush>
    <x-slot:badge>
        @if ($previous->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ $previous->count() >= 2 ? 'bg-warn-bg text-warn-ink' : 'bg-line-soft text-ink-body' }}">{{ $previous->count() }}</span>
        @endif
    </x-slot:badge>

    @if ($previous->isEmpty())
        <p class="m-0 px-5 py-4 text-[13px] text-ink-grey">Aucune réparation sur {{ $place }} depuis {{ \App\Models\WorkOrder::PREVIOUS_REPAIRS_DAYS / 30 }} mois.</p>
    @else
        @if ($previous->count() >= 2)
            <p class="m-0 px-5 pt-3.5 text-[12.5px] font-semibold text-warn-ink">La panne revient : {{ $previous->count() }} réparations sur {{ $place }} en {{ \App\Models\WorkOrder::PREVIOUS_REPAIRS_DAYS / 30 }} mois.</p>
        @endif
        <ul class="m-0 p-0 list-none divide-y divide-line-soft">
            @foreach ($previous as $repair)
                @php
                    $report = $repair->interventionReport;
                    $done = $report?->work_performed;
                    $advice = $report?->recommendations;
                @endphp
                <li class="px-5 py-3">
                    <p class="m-0 flex items-baseline justify-between gap-3">
                        @can('view', $repair)
                            <a href="{{ route('work-orders.show', $repair) }}" class="text-[13.5px] font-semibold text-navy hover:underline truncate">{{ $repair->title }}</a>
                        @else
                            <span class="text-[13.5px] font-semibold text-navy truncate">{{ $repair->title }}</span>
                        @endcan
                        <span class="font-mono text-[11.5px] text-ink-grey whitespace-nowrap">{{ $repair->completed_at->format('d/m/Y') }}</span>
                    </p>
                    <p class="m-0 mt-0.5 text-[12px] text-ink-muted">{{ $repair->code() }} · {{ $repair->assignee?->name ?? '—' }}</p>
                    @if (filled($done))
                        <p class="m-0 mt-1 text-[12.5px] text-ink-strong line-clamp-2">{{ $done }}</p>
                    @endif
                    @if (filled($advice))
                        <p class="m-0 mt-1 text-[12.5px] text-ink-body line-clamp-2"><span class="font-semibold">Conseil :</span> {{ $advice }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>
