<x-app-layout :crumb="'Exploitation'" :page-title="'Maintenance préventive'">
    <x-slot:primaryAction>
        <a href="{{ route('maintenance-plans.create') }}" class="btn btn-primary">+ Nouveau plan</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <p class="text-sm text-gray-500 mb-4">
                Chaque plan génère automatiquement un ordre de travail à son échéance, avec assignation
                intelligente du technicien selon les compétences requises et la charge de travail.
                La génération tourne chaque jour à 05h00 (commande <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">maintenance:generate-preventive-work-orders</code>).
            </p>

            <!-- Filtres -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" action="{{ route('maintenance-plans.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Statut</label>
                        <select name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Tous</option>
                            <option value="actif" @selected(request('status') === 'actif')>Actif</option>
                            <option value="inactif" @selected(request('status') === 'inactif')>Inactif</option>
                        </select>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Filtrer
                    </button>

                    @if (request()->hasAny(['status']))
                        <a href="{{ route('maintenance-plans.index') }}" class="text-sm text-gray-500 underline">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cible</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fréquence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prochaine échéance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($plans as $plan)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $plan->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $plan->equipment?->name ?? $plan->room?->label ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $plan->frequency_label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $plan->assignee?->name ?? ($plan->requiredSkill?->name ? 'Auto (' . $plan->requiredSkill->name . ')' : 'Non assigné') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $plan->next_due_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($plan->is_active)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('maintenance-plans.show', $plan) }}" class="text-indigo-600 hover:text-indigo-900">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Aucun plan de maintenance préventive défini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $plans->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
