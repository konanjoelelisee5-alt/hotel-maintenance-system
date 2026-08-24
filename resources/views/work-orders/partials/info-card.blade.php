<div class="bg-white rounded-xl border border-slate-200">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-navy-900 text-sm">Informations</h3>
    </div>
    <dl class="p-5 space-y-3 text-sm">
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Lieu</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->room ? 'Chambre ' . $workOrder->room->number : '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Équipement</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->equipment?->name ?? '—' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Type</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->type->label }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Assigné à</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->assignee?->name ?? 'Non assigné' }}</dd>
        </div>
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Signalé par</dt>
            <dd class="text-slate-800 font-medium text-right">{{ $workOrder->reporter?->name ?? '—' }}</dd>
        </div>
        <div class="border-t border-slate-100 pt-3 flex justify-between gap-3">
            <dt class="text-slate-500">Créé le</dt>
            <dd class="text-slate-800 text-right">{{ $workOrder->created_at->format('d/m/Y H:i') }}</dd>
        </div>
        @if ($workOrder->scheduled_at)
            <div class="flex justify-between gap-3">
                <dt class="text-slate-500">Planifié le</dt>
                <dd class="text-slate-800 text-right">{{ $workOrder->scheduled_at->format('d/m/Y H:i') }}</dd>
            </div>
        @endif
        <div class="flex justify-between gap-3">
            <dt class="text-slate-500">Échéance</dt>
            <dd class="text-right {{ $workOrder->due_date && $workOrder->due_date->isPast() && ! in_array($workOrder->status, ['resolu', 'ferme']) ? 'text-red-600 font-semibold' : 'text-slate-800' }}">
                {{ $workOrder->due_date?->format('d/m/Y H:i') ?? '—' }}
            </dd>
        </div>
    </dl>

    @if ($workOrder->maintenance_plan_id && in_array(auth()->user()->role, ['admin', 'manager']))
        <div class="px-5 py-3 border-t border-slate-100 bg-navy-50 rounded-b-xl">
            <a href="{{ route('maintenance-plans.show', $workOrder->maintenance_plan_id) }}"
               class="flex items-center gap-2 text-xs text-navy-700 hover:underline">
                <span>⟳</span>
                <span>Généré automatiquement — voir le plan de maintenance préventive</span>
            </a>
        </div>
    @endif
</div>
