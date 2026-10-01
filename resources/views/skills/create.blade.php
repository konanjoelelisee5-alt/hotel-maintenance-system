<x-form-page title="Nouvelle compétence" crumb="Référentiels" icon="star"
             subtitle="Savoir-faire d'un technicien (plomberie, climatisation...), utilisé pour l'affectation.">
    <form method="POST" action="{{ route('skills.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nom de la compétence" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('skills.index') }}" data-modal-close class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
        </div>
    </form>
</x-form-page>
