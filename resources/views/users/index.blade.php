<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Utilisateurs') }}</h2>
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Nouvel utilisateur
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="mb-4 p-4 bg-orange-100 text-orange-800 rounded-md">{{ session('warning') }}</div>
            @endif

            <div class="bg-white p-4 rounded-lg shadow-sm mb-4">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom..."
                        class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                    <select name="role" class="border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Tous les rôles</option>
                        <option value="admin" @selected(request('role') === 'admin')>Administrateur</option>
                        <option value="manager" @selected(request('role') === 'manager')>Manager</option>
                        <option value="technicien" @selected(request('role') === 'technicien')>Technicien</option>
                        <option value="housekeeping" @selected(request('role') === 'housekeeping')>Housekeeping</option>
                        <option value="reception" @selected(request('role') === 'reception')>Réception</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $user->role_label }}</td>
                                <td class="px-6 py-4">
                                    @if ($user->is_active)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full">Désactivé</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Modifier</a>
                                    @if ($user->is_active && $user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                              onsubmit="return confirm('Désactiver ce compte ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Désactiver</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Aucun utilisateur trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>

        </div>
    </div>
</x-app-layout>