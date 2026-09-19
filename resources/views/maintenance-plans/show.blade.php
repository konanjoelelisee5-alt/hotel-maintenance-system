<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $maintenancePlan->name }}</h2>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('maintenance-plans.generate', $maintenancePlan) }}"
                      onsubmit="return confirm('Générer un OT maintenant pour ce plan, sans attendre son échéance ?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-navy-700 text-white text-sm font-medium rounded-md hover:bg-navy-800">
                        Générer un OT maintenant
                    </button>
                </form>
                <a href="{{ route('maintenance-plans.edit', $maintenancePlan) }}"
                   class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                    Modifier
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white p-6 shadow-sm rounded-lg grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Cible</div>
                    <div class="text-gray-900">{{ $maintenancePlan->equipment?->name ?? ($maintenancePlan->room ? 'Chambre ' . $maintenancePlan->room->number : '—') }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Type d'OT généré</div>
                    <div class="text-gray-900">{{ $maintenancePlan->type->label }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Priorité</div>
                    <div class="text-gray-900">{{ $maintenancePlan->priority->label }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Fréquence</div>
                    <div class="text-gray-900">{{ $maintenancePlan->frequency_label }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Préavis de génération</div>
                    <div class="text-gray-900">{{ $maintenancePlan->lead_time_days }} jour(s)</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Statut</div>
                    <div>
                        @if ($maintenancePlan->is_active)
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                        @else
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Inactif</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Assignation</div>
                    <div class="text-gray-900">
                        {{ $maintenancePlan->assignee?->name ?? ($maintenancePlan->requiredSkill?->name ? 'Auto — compétence « ' . $maintenancePlan->requiredSkill->name . ' »' : 'Non assigné') }}
                    </div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Checklist</div>
                    <div class="text-gray-900">{{ $maintenancePlan->checklistTemplate?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Prochaine échéance</div>
                    <div class="text-gray-900 font-medium">{{ $maintenancePlan->next_due_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Dernière génération</div>
                    <div class="text-gray-900">{{ $maintenancePlan->last_generated_at?->format('d/m/Y H:i') ?? 'Jamais' }}</div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Période</div>
                    <div class="text-gray-900">
                        {{ $maintenancePlan->start_date->format('d/m/Y') }}
                        @if ($maintenancePlan->end_date) → {{ $maintenancePlan->end_date->format('d/m/Y') }} @endif
                    </div>
                </div>
                <div>
                    <div class="text-gray-400 uppercase text-xs mb-1">Créé par</div>
                    <div class="text-gray-900">{{ $maintenancePlan->creator?->name ?? '—' }}</div>
                </div>

                @if ($maintenancePlan->description)
                    <div class="col-span-2 md:col-span-3">
                        <div class="text-gray-400 uppercase text-xs mb-1">Description</div>
                        <div class="text-gray-900 whitespace-pre-line">{{ $maintenancePlan->description }}</div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-medium text-gray-800">Ordres de travail générés</h3>
                </div>
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigné à</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($recentWorkOrders as $workOrder)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">#{{ $workOrder->id }} — {{ $workOrder->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $workOrder->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $workOrder->assignee?->name ?? 'Non assigné' }}</td>
                                <td class="px-6 py-4"><x-work-order-status-badge :status="$workOrder->status" /></td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-orders.show', $workOrder) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Aucun OT généré pour l'instant.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <form method="POST" action="{{ route('maintenance-plans.destroy', $maintenancePlan) }}"
                  onsubmit="return confirm('Supprimer définitivement ce plan de maintenance préventive ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:underline">Supprimer ce plan</button>
            </form>

        </div>
    </div>
</x-app-layout>
