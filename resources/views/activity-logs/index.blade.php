<x-app-layout :crumb="'Paramètres'" page-title="Journal d'activité" :back-route="route('settings.index')">
    <div>
        <div class="w-full space-y-4">

            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" class="flex flex-wrap gap-4">
                    <select name="user_id" class="border-line rounded-md shadow-sm text-sm">
                        <option value="">Tous les utilisateurs</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select name="action" class="border-line rounded-md shadow-sm text-sm">
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
                    <label class="flex items-center gap-2 text-sm text-ink-muted">
                        Du <input type="date" name="date_from" value="{{ request('date_from') }}" class="border-line rounded-md shadow-sm text-sm">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink-muted">
                        au <input type="date" name="date_to" value="{{ request('date_to') }}" class="border-line rounded-md shadow-sm text-sm">
                    </label>
                    <button type="submit" class="btn btn-secondary">
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-ink-muted whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-navy">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted font-mono">{{ $log->action }}</td>
                                <td class="px-6 py-4 text-sm text-ink-body">{{ $log->description }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted font-mono whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Aucune activité enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $logs->links() }}</div>

        </div>
    </div>
</x-app-layout>