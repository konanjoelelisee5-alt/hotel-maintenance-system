<x-app-layout :crumb="'Exploitation'" :page-title="'Maintenance préventive'">
    <x-slot:primaryAction>
        <a href="{{ route('maintenance-plans.create') }}" class="btn btn-primary">+ Nouveau plan</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <p class="text-sm text-ink-muted mb-4">
                Chaque plan génère automatiquement un ordre de travail à son échéance, avec assignation
                intelligente du technicien selon les compétences requises et la charge de travail.
                La génération tourne chaque jour à 05h00 (commande <code class="text-xs bg-line-soft px-1 py-0.5 rounded">maintenance:generate-preventive-work-orders</code>).
            </p>

            <!-- Filtres -->
            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" action="{{ route('maintenance-plans.index') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-ink-body">Statut</label>
                        <select name="status" class="mt-1 rounded-md border-line text-sm">
                            <option value="">Tous</option>
                            <option value="actif" @selected(request('status') === 'actif')>Actif</option>
                            <option value="inactif" @selected(request('status') === 'inactif')>Inactif</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        Filtrer
                    </button>

                    @if (request()->hasAny(['status']))
                        <a href="{{ route('maintenance-plans.index') }}" class="text-sm text-ink-muted underline">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Cible</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Fréquence</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Assignation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Prochaine échéance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($plans as $plan)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">{{ $plan->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">
                                    {{ $plan->equipment?->name ?? $plan->room?->label ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $plan->frequency_label }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">
                                    {{ $plan->assignee?->name ?? ($plan->requiredSkill?->name ? 'Auto (' . $plan->requiredSkill->name . ')' : 'Non assigné') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-ink-muted">
                                    {{ $plan->next_due_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($plan->is_active)
                                        <x-badge color="green">Actif</x-badge>
                                    @else
                                        <x-badge color="muted">Inactif</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('maintenance-plans.show', $plan) }}" class="text-blue hover:text-navy">Voir</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-ink-muted">
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
