<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Journal d\'activité') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                        <option value="user." @selected(request('action') === 'user.')>Utilisateurs</option>
                        <option value="quality_control." @selected(request('action') === 'quality_control.')>Contrôle qualité</option>
                    </select>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $log->action }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Aucune activité enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div>{{ $logs->links() }}</div>

        </div>
    </div>
</x-app-layout>