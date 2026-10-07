<x-app-layout :crumb="'Paramètres'" page-title="Types d'ordres de travail" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('work-order-types.create') }}" data-modal class="btn btn-primary">+ Nouveau type</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full max-w-4xl">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Libellé</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($types as $type)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">{{ $type->label }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted font-mono">{{ $type->code }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $type->position }}</td>
                                <td class="px-6 py-4">
                                    @if ($type->is_active)
                                        <x-badge color="green">Actif</x-badge>
                                    @else
                                        <x-badge color="muted">Inactif</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-order-types.edit', $type) }}" class="text-blue hover:text-navy">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Aucun type défini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>