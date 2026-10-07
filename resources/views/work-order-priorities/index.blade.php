<x-app-layout :crumb="'Paramètres'" :page-title="'Priorités'" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('work-order-priorities.create') }}" data-modal class="btn btn-primary">+ Nouvelle priorité</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full max-w-4xl">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Aperçu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($priorities as $priority)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4">
                                    <x-work-order-priority-badge :priority="$priority" />
                                </td>
                                <td class="px-6 py-4 text-sm text-ink-muted font-mono">{{ $priority->code }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $priority->position }}</td>
                                <td class="px-6 py-4">
                                    @if ($priority->is_active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="muted">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-order-priorities.edit', $priority) }}" class="text-blue hover:text-navy">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Aucune priorité définie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>