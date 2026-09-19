@php
    $slaClosed = in_array($workOrder->status, ['resolu', 'ferme']);
@endphp

<x-accordion-card title="SLA" :open="$workOrder->sla_breached">
    <x-slot:badge>
        @if ($workOrder->sla_breached)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Dépassé</span>
        @elseif (! $slaClosed && $workOrder->sla_resolution_due_at)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ \App\Support\Swatch::soft($workOrder->slaColorClass()) }} {{ \App\Support\Swatch::text($workOrder->slaColorClass()) }}">{{ $workOrder->slaProgressPercent() }} % consommé</span>
        @endif
    </x-slot:badge>

    @php
        $fmtDuration = fn (int $minutes) => sprintf('%dh%02d', intdiv(max(0, $minutes), 60), max(0, $minutes) % 60);
    @endphp
    @if ($workOrder->sla_resolution_due_at)
        <div class="mb-4">
            <span class="block h-[6px] rounded-full bg-line-soft overflow-hidden">
                <span class="block h-full {{ \App\Support\Swatch::bg($workOrder->slaColorClass()) }}" style="width: {{ $workOrder->slaProgressPercent() }}%"></span>
            </span>
            <p class="text-[12px] text-ink-grey mt-1.5">
                Temps écoulé : {{ $fmtDuration($workOrder->created_at->diffInMinutes(now())) }} / {{ $fmtDuration($workOrder->created_at->diffInMinutes($workOrder->sla_resolution_due_at)) }} · échéance {{ $workOrder->sla_resolution_due_at->format('H:i') }}
            </p>
        </div>
    @endif

    <dl class="space-y-3 text-sm">
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Politique</dt>
            <dd class="text-[#3d3a33] font-medium text-right">{{ $workOrder->slaPolicy?->name ?? '—' }}</dd>
        </div>
        @if ($workOrder->sla_response_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-ink-grey">Réponse due</dt>
                <dd class="text-[#3d3a33] text-right">{{ $workOrder->sla_response_due_at->format('d/m/Y H:i') }}</dd>
            </div>
        @endif
        @if ($workOrder->sla_resolution_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-ink-grey">Résolution due</dt>
                <dd class="text-right {{ $workOrder->sla_breached ? 'text-red-600 font-semibold' : 'text-[#3d3a33]' }}">
                    {{ $workOrder->sla_resolution_due_at->format('d/m/Y H:i') }}
                </dd>
            </div>
        @endif
    </dl>
</x-accordion-card>
