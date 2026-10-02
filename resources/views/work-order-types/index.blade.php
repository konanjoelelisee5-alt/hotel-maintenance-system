<x-app-layout :crumb="'Référentiels'" page-title="Types d'ordres de travail">
    <x-slot:primaryAction>
        <a href="{{ route('work-order-types.create') }}" data-modal class="btn btn-primary">+ Nouveau type</a>
    </x-slot:primaryAction>

    <div>
        <div class="w-full max-w-4xl">


            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Libellé</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($types as $type)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $type->label }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $type->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $type->position }}</td>
                                <td class="px-6 py-4">
                                    @if ($type->is_active)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('work-order-types.edit', $type) }}" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Aucun type défini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>