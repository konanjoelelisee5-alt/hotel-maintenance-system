<x-app-layout :crumb="'Paramètres'" :page-title="'Compétences'" :back-route="route('settings.index')">
    <x-slot:primaryAction>
        <a href="{{ route('skills.create') }}" data-modal class="btn btn-primary">+ Nouvelle compétence</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full max-w-3xl">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-paper">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Techniciens</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($skills as $skill)
                            <tr class="hover:bg-paper">
                                <td class="px-6 py-4 text-sm text-navy">{{ $skill->name }}</td>
                                <td class="px-6 py-4 text-sm text-ink-muted">{{ $skill->users_count }}</td>
                                <td class="px-6 py-4">
                                    @if ($skill->is_active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="muted">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('skills.edit', $skill) }}" class="text-blue hover:text-navy">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-ink-muted">Aucune compétence définie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>