<x-app-layout :crumb="'Paramètres'" page-title="Règles d'escalade" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('escalation-rules.create') }}" class="btn btn-primary">+ Nouvelle règle</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Déclencheur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Décalage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Notifie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($rules as $rule)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">{{ $rule->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $rule->trigger_type_label }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $rule->offset_minutes }} min</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $rule->notify_target_label }}</td>
                                <td class="px-6 py-4">
                                    @if ($rule->is_active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="muted">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('escalation-rules.edit', $rule) }}" class="text-blue hover:text-navy">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-ink-muted">Aucune règle définie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>