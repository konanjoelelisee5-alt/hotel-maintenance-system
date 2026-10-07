<x-form-page title="Nouveau type d'OT" crumb="Paramètres" icon="tag"
             subtitle="Nature d'intervention proposée à la création d'un ordre de travail.">
    <form method="POST" action="{{ route('work-order-types.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="label" value="Libellé affiché" />
            <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" :value="old('label')" required />
            <x-input-error :messages="$errors->get('label')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="code" value="Code technique (unique, ex: inspection)" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
            <p class="text-xs text-ink-muted mt-1">Sans espace ni accent, utilisé en interne. Ne pourra plus être modifié après création.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="position" value="Position d'affichage" />
            <x-text-input id="position" name="position" type="number" min="0" class="mt-1 block w-full" :value="old('position', 0)" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('work-order-types.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
