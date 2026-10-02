@php
    $slaClosed = in_array($workOrder->status, \App\Models\WorkOrder::FINISHED_STATUSES);
    // Échéance passée = dépassé, sans attendre que la tâche planifiée pose sla_breached.
    $slaLate = $workOrder->sla_breached || (! $slaClosed && $workOrder->sla_resolution_due_at?->isPast());
    $slaColor = $slaLate ? 'red' : $workOrder->slaColorClass();
@endphp

<x-panel title="Délai SLA" icon="shield">
    <x-slot:badge>
        @if ($slaLate)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11.5px] font-semibold bg-[#FBE4E1] text-red">Dépassé</span>
        @elseif (! $slaClosed && $workOrder->sla_resolution_due_at)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ \App\Support\Swatch::soft($slaColor) }} {{ \App\Support\Swatch::text($slaColor) }}">{{ $workOrder->slaProgressPercent() }} % consommé</span>
        @endif
    </x-slot:badge>

    @if ($workOrder->sla_resolution_due_at && ! $slaClosed)
        <div class="mb-4">
            <span class="block h-[6px] rounded-full bg-line-soft overflow-hidden">
                <span class="block h-full {{ \App\Support\Swatch::bg($slaColor) }}" style="width: {{ $slaLate ? 100 : $workOrder->slaProgressPercent() }}%"></span>
            </span>
            <p class="m-0 text-[12px] text-ink-grey mt-1.5">
                Écoulé : {{ \App\Support\Duration::human($workOrder->created_at->diffInMinutes(now())) }}
                sur un délai de {{ \App\Support\Duration::human($workOrder->created_at->diffInMinutes($workOrder->sla_resolution_due_at)) }}
            </p>
        </div>
    @endif

    <dl class="m-0 flex flex-col gap-2.5 text-[13px]">
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Politique</dt>
            <dd class="m-0 font-medium text-right">{{ $workOrder->slaPolicy?->name ?? '—' }}</dd>
        </div>
        @if ($workOrder->sla_response_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-ink-grey">Réponse attendue</dt>
                <dd class="m-0 text-right">{{ $workOrder->sla_response_due_at->format('d/m/Y à H\hi') }}</dd>
            </div>
        @endif
        @if ($workOrder->sla_resolution_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-ink-grey">Résolution attendue</dt>
                <dd class="m-0 text-right {{ $workOrder->sla_breached ? 'text-red font-semibold' : '' }}">{{ $workOrder->sla_resolution_due_at->format('d/m/Y à H\hi') }}</dd>
            </div>
        @endif
    </dl>
</x-panel>
