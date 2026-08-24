@php
    $slaClosed = in_array($workOrder->status, ['resolu', 'ferme']);
@endphp

<div class="bg-white rounded-xl border {{ $workOrder->sla_breached ? 'border-red-200' : 'border-slate-200' }}">
    <div class="px-5 py-4 border-b {{ $workOrder->sla_breached ? 'border-red-100' : 'border-slate-100' }} flex items-center justify-between">
        <h3 class="font-semibold text-navy-900 text-sm">SLA</h3>
        @if ($workOrder->sla_breached)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                Dépassé
            </span>
        @elseif (! $slaClosed)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                Dans les délais
            </span>
        @endif
    </div>
    <dl class="p-5 space-y-3 text-sm">
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Politique</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->slaPolicy?->name ?? '—' }}</dd>
        </div>
        @if ($workOrder->sla_response_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-slate-500">Réponse due</dt>
                <dd class="text-slate-800 text-right">{{ $workOrder->sla_response_due_at->format('d/m/Y H:i') }}</dd>
            </div>
        @endif
        @if ($workOrder->sla_resolution_due_at)
            <div class="flex justify-between gap-3">
                <dt class="text-slate-500">Résolution due</dt>
                <dd class="text-right {{ $workOrder->sla_breached ? 'text-red-600 font-semibold' : 'text-slate-800' }}">
                    {{ $workOrder->sla_resolution_due_at->format('d/m/Y H:i') }}
                </dd>
            </div>
        @endif
    </dl>
</div>
