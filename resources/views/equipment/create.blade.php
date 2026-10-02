<x-form-page title="Nouvel équipement" crumb="Patrimoine" icon="wrench"
             subtitle="Appareil suivi par la maintenance : son historique de pannes s'y rattachera.">
    <form method="POST" action="{{ route('equipment.store') }}" class="space-y-6">
        @csrf
        @include('equipment._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('equipment.index') }}" data-modal-close class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
        </div>
    </form>
</x-form-page>
