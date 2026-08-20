<x-guest-layout>
    <div class="text-center py-12">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">403 — Accès refusé</h1>
        <p class="text-gray-600 mb-6">Vous n'avez pas les autorisations nécessaires pour accéder à cette page.</p>
        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
            Retour
        </a>
    </div>
</x-guest-layout>