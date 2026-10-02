<x-app-layout :crumb="'Alertes & SLA'" :page-title="'Politiques SLA'">
    <x-slot:primaryAction>
        <a href="{{ route('sla-policies.create') }}" class="btn btn-primary">+ Nouvelle politique</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priorité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type OT</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Délai réponse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Délai résolution</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($policies as $policy)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $policy->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $policy->priority ?? 'Toutes' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $policy->work_order_type ?? 'Tous' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $policy->response_time_minutes }} min</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $policy->resolution_time_minutes }} min</td>
                                <td class="px-6 py-4">
                                    @if ($policy->is_active)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('sla-policies.edit', $policy) }}" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Aucune politique SLA définie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>