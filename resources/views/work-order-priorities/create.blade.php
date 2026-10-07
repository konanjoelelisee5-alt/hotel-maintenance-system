<x-form-page title="Nouvelle priorité" crumb="Paramètres" icon="flag"
             subtitle="Niveau d'urgence, avec sa couleur et son rang dans les listes.">
    <form method="POST" action="{{ route('work-order-priorities.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="label" value="Libellé affiché" />
            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label')" required />
            <x-input-error :messages="$errors->get('label')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Code technique (unique, ex: critique)" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
            <p class="text-xs text-ink-muted mt-1">Sans espace ni accent. Ne pourra plus être modifié après création.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="color" value="Couleur" />
            <input type="color" id="color" name="color" value="{{ old('color', '#6b7280') }}" class="mt-1 block w-24 h-10 border-line rounded-md shadow-sm">
            <x-input-error :messages="$errors->get('color')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="position" value="Position d'affichage" />
            <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', 0)" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('work-order-priorities.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
