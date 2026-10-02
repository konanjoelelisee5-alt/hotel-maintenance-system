<x-form-page title="Nouveau lieu" crumb="Patrimoine" icon="building"
             subtitle="Chambre ou espace commun : il pourra être choisi dans les ordres de travail.">
    <form method="POST" action="{{ route('rooms.store') }}" class="space-y-6">
        @csrf
        @include('rooms._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('rooms.index') }}" data-modal-close class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
        </div>
    </form>
</x-form-page>
