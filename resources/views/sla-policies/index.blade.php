<x-app-layout :crumb="'Paramètres'" :page-title="'Politiques SLA'" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('sla-policies.create') }}" class="btn btn-primary">+ Nouvelle politique</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Priorité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Type OT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Délai réponse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Délai résolution</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($policies as $policy)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">{{ $policy->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $policy->priority ?? 'Toutes' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $policy->work_order_type ?? 'Tous' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $policy->response_time_minutes }} min</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $policy->resolution_time_minutes }} min</td>
                                <td class="px-6 py-4">
                                    @if ($policy->is_active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="muted">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('sla-policies.edit', $policy) }}" class="text-blue hover:text-navy">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-ink-muted">Aucune politique SLA définie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>