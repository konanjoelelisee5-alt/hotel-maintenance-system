<div class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft">
        <h3 class="font-semibold text-navy-900 text-sm">Informations</h3>
    </div>
    <dl class="p-5 space-y-3 text-sm">
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Lieu</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->room?->label ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Équipement</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->equipment?->name ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Type</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->type->label }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Assigné à</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->assignee?->name ?? 'Non assigné' }}</dd>
        </div>
        {{-- Résumé du chrono pour qui n'a pas le bloc « Suivi de l'intervention »
             (superviseurs) : seul l'intervenant assigné chronomètre. --}}
        @cannot('perform', $workOrder)
            @php $activeSession = $workOrder->activeSession(); @endphp
            @if ($activeSession || $workOrder->total_worked_minutes > 0)
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-grey">Temps passé</dt>
                    <dd class="text-slate-800 font-medium text-right">
                        {{ $workOrder->total_worked_minutes }} min
                        @if ($activeSession)
                            <span class="block text-xs text-emerald-600 font-semibold">● intervention en cours depuis {{ $activeSession->started_at->format('H:i') }}</span>
                        @endif
                    </dd>
                </div>
            @endif
        @endcannot
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Signalé par</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->reporter?->name ?? '—' }}</dd>
        </div>
        <div class="border-t border-line-soft pt-3 flex justify-between gap-3">
            <dt class="text-ink-grey">Créé le</dt>
            <dd class="text-slate-800 text-right">{{ $workOrder->created_at->format('d/m/Y H:i') }}</dd>
        </div>
        @if ($workOrder->scheduled_at)
            <div class="flex justify-between gap-3">
                <dt class="text-ink-grey">Planifié le</dt>
                <dd class="text-slate-800 text-right">{{ $workOrder->scheduled_at->format('d/m/Y H:i') }}</dd>
            </div>
        @endif
        <div class="flex justify-between gap-3">
            <dt class="text-ink-grey">Échéance</dt>
            <dd class="text-right {{ $workOrder->due_date && $workOrder->due_date->isPast() && ! in_array($workOrder->status, ['resolu', 'ferme']) ? 'text-red-600 font-semibold' : 'text-slate-800' }}">
                {{ $workOrder->due_date?->format('d/m/Y H:i') ?? '—' }}
            </dd>
        </div>
    </dl>

    @if ($workOrder->maintenance_plan_id && in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
        <div class="px-5 py-3 border-t border-line-soft bg-navy-50 rounded-b-xl">
            <a href="{{ route('maintenance-plans.show', $workOrder->maintenance_plan_id) }}"
               class="flex items-center gap-2 text-xs text-navy-700 hover:underline">
                <span>⟳</span>
                <span>Généré automatiquement — voir le plan de maintenance préventive</span>
            </a>
        </div>
    @endif
</div>
