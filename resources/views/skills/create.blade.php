<x-form-page title="Nouvelle compétence" crumb="Paramètres" icon="star"
             subtitle="Savoir-faire d'un technicien (plomberie, climatisation...), utilisé pour l'affectation.">
    <form method="POST" action="{{ route('skills.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nom de la compétence" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('skills.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
