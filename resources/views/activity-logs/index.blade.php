<x-app-layout :crumb="'Paramètres'" page-title="Journal d'activité" :back-route="route('settings.index')">
    <div>
        <div class="w-full space-y-4">

            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" class="flex flex-wrap gap-4">
                    <select name="user_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les utilisateurs</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select name="action" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Toutes les actions</option>
                        @foreach ([
                            'auth.' => 'Connexions',
                            'auth.failed' => '— Échecs de connexion',
                            'user.' => 'Utilisateurs',
                            'work_order.' => 'Ordres de travail',
                            'quality_control.' => 'Contrôle qualité',
                            'report.' => 'Exports de rapports',
                            'room.' => 'Lieux',
                            'equipment.' => 'Équipements',
                            'sla_policy.' => 'Politiques SLA',
                            'escalation_rule.' => "Règles d'escalade",
                            'setting.' => 'Paramètres (astreinte)',
                            'work_order_priority.' => 'Priorités',
                            'work_order_type.' => "Types d'OT",
                            'skill.' => 'Compétences',
                        ] as $prefix => $label)
                            <option value="{{ $prefix }}" @selected(request('action') === $prefix)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        Du <input type="date" name="date_from" value="{{ request('date_from') }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        au <input type="date" name="date_to" value="{{ request('date_to') }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                    </label>
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $log->action }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->description }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Aucune activité enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $logs->links() }}</div>

        </div>
    </div>
</x-app-layout>