<x-app-layout :crumb="'Exploitation / Maintenance préventive'" :page-title="$maintenancePlan->name" :back-route="route('maintenance-plans.index')">
    <x-slot:primaryAction>
        <form method="POST" action="{{ route('maintenance-plans.generate', $maintenancePlan) }}"
              data-confirm="Un ordre de travail est créé tout de suite, sans attendre l’échéance du plan."
              data-confirm-title="Générer un OT maintenant ?" data-confirm-label="Générer l’OT">
            @csrf
            <button type="submit" class="btn btn-primary">Générer un OT maintenant</button>
        </form>
        <x-more-menu>
            <x-more-menu.item :href="route('maintenance-plans.edit', $maintenancePlan)" icon="pencil">Modifier le plan</x-more-menu.item>
            <x-more-menu.separator />
            <x-more-menu.item :action="route('maintenance-plans.destroy', $maintenancePlan)" method="DELETE" icon="trash" danger
                              confirm="Le plan ne générera plus d’OT. Les OT déjà créés sont conservés."
                              confirm-title="Supprimer ce plan de maintenance ?" confirm-label="Supprimer le plan">Supprimer le plan</x-more-menu.item>
        </x-more-menu>
    </x-slot:primaryAction>

    <div>
        <div class="w-full space-y-6">


            <div class="bg-white p-6 shadow-sm rounded-lg grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Cible</div>
                    <div class="text-navy">{{ $maintenancePlan->equipment?->name ?? ($maintenancePlan->room?->label ?? '—') }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Type d'OT généré</div>
                    <div class="text-navy">{{ $maintenancePlan->type->label }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Priorité</div>
                    <div class="text-navy">{{ $maintenancePlan->priority->label }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Fréquence</div>
                    <div class="text-navy">{{ $maintenancePlan->frequency_label }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Préavis de génération</div>
                    <div class="text-navy">{{ $maintenancePlan->lead_time_days }} jour(s)</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Statut</div>
                    <div>
                        @if ($maintenancePlan->is_active)
                            <x-badge color="green">Actif</x-badge>
                        @else
                            <x-badge color="muted">Inactif</x-badge>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Assignation</div>
                    <div class="text-navy">
                        {{ $maintenancePlan->assignee?->name ?? ($maintenancePlan->requiredSkill?->name ? 'Auto — compétence « ' . $maintenancePlan->requiredSkill->name . ' »' : 'Non assigné') }}
                    </div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Checklist</div>
                    <div class="text-navy">{{ $maintenancePlan->checklistTemplate?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Prochaine échéance</div>
                    <div class="text-navy font-medium">{{ $maintenancePlan->next_due_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Dernière génération</div>
                    <div class="text-navy">{{ $maintenancePlan->last_generated_at?->format('d/m/Y H:i') ?? 'Jamais' }}</div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Période</div>
                    <div class="text-navy">
                        {{ $maintenancePlan->start_date->format('d/m/Y') }}
                        @if ($maintenancePlan->end_date) → {{ $maintenancePlan->end_date->format('d/m/Y') }} @endif
                    </div>
                </div>
                <div>
                    <div class="text-ink-faint uppercase text-xs mb-1">Créé par</div>
                    <div class="text-navy">{{ $maintenancePlan->creator?->name ?? '—' }}</div>
                </div>

                @if ($maintenancePlan->description)
                    <div class="col-span-2 md:col-span-3">
                        <div class="text-ink-faint uppercase text-xs mb-1">Description</div>
                        <div class="text-navy whitespace-pre-line">{{ $maintenancePlan->description }}</div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-medium text-ink-deep">Ordres de travail générés</h3>
                </div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">OT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Échéance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Assigné à</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($recentWorkOrders as $workOrder)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">#{{ $workOrder->id }} — {{ $workOrder->title }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $workOrder->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $workOrder->assignee?->name ?? 'Non assigné' }}</td>
                                <td class="px-6 py-4"><x-work-order-status-badge :status="$workOrder->status" /></td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-orders.show', $workOrder) }}" class="text-blue hover:text-navy">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">
                                    Aucun OT généré pour l'instant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>


        </div>
    </div>
</x-app-layout>
