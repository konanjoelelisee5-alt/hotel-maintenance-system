<x-form-page title="Nouvelle pièce" crumb="Stock & achats" icon="part"
             subtitle="Article du magasin : quantité, seuil d'alerte et coût.">
    <form method="POST" action="{{ route('parts.store') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="sku" value="Référence (SKU)" />
            <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku')" required />
            <x-input-error :messages="$errors->get('sku')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="name" value="Nom de la pièce" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="unit" value="Unité (ex: unité, mètre, litre)" />
                <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', 'unité')" />
            </div>
            <div>
                <x-input-label for="reorder_threshold" value="Seuil d'alerte" />
                <x-text-input id="reorder_threshold" name="reorder_threshold" type="number" min="0" class="mt-1 block w-full" :value="old('reorder_threshold', 5)" required />
                <x-input-error :messages="$errors->get('reorder_threshold')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="unit_cost" value="Coût unitaire en FCFA (optionnel)" />
            <x-text-input id="unit_cost" name="unit_cost" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('unit_cost')" />
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('parts.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
