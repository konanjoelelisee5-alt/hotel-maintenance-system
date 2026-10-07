<x-app-layout :crumb="'Patrimoine / Équipements'" :page-title="$equipment->name" :back-route="route('equipment.index')">
    <x-slot:primaryAction>
        <a href="{{ route('equipment.edit', $equipment) }}" class="btn btn-secondary"><x-nav-icon name="pencil" /> Modifier</a>
        @if ($equipment->status !== 'hors_service')
            <x-more-menu>
                <x-more-menu.item :action="route('equipment.destroy', $equipment)" method="DELETE" icon="ban" danger
                                  confirm="Il ne sera plus proposé pour les nouveaux signalements. Son historique est conservé." confirm-title="Mettre cet équipement hors service ?" confirm-label="Mettre hors service">Mettre hors service</x-more-menu.item>
            </x-more-menu>
        @endif
    </x-slot:primaryAction>

    <p class="flex items-center gap-1.5 flex-wrap text-[13.5px] text-ink-grey">
        {{ $equipment->type ?? 'Équipement' }} ·
        @if ($equipment->room)
            <a href="{{ route('rooms.show', $equipment->room) }}" class="text-navy font-medium hover:underline">{{ $equipment->room->label }}</a>
        @else
            <span class="text-amber font-medium">sans lieu</span>
        @endif
        · @include('rooms.partials.status-badge', ['status' => $equipment->status, 'label' => $equipment->status_label])
    </p>

    <div>
        <div class="w-full space-y-6">

            <div class="grid grid-cols-3 gap-4">
                @foreach ([['OT ouverts', $stats['open']], ['OT sur 90 jours', $stats['last90']], ['OT au total', $stats['total']]] as [$label, $value])
                    <div class="bg-white p-4 shadow-sm rounded-lg">
                        <div class="text-xs text-ink-muted uppercase">{{ $label }}</div>
                        <div class="text-2xl font-semibold text-navy mt-1">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @if ($equipment->maintenancePlans->isNotEmpty())
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-semibold text-ink-deep mb-3">Maintenance préventive</h3>
                    <ul class="divide-y divide-line-soft text-sm">
                        @foreach ($equipment->maintenancePlans as $plan)
                            <li class="py-2 flex justify-between gap-3">
                                <a href="{{ route('maintenance-plans.show', $plan) }}" class="text-navy hover:underline">{{ $plan->name }}</a>
                                <span class="text-ink-muted">
                                    {{ $plan->is_active ? 'Prochaine échéance : '.($plan->next_due_at?->format('d/m/Y') ?? '—') : 'Plan inactif' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('rooms.partials.work-order-history', ['workOrders' => $workOrders])
        </div>
    </div>
</x-app-layout>
