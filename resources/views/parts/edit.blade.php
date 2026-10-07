<x-form-page :title="'Modifier '.$part->name" crumb="Stock & achats / Pièces & stock" icon="part"
             subtitle="La référence (SKU) ne change pas ; les quantités se modifient par un mouvement de stock."
             :back="route('parts.show', $part)">
    <form method="POST" action="{{ route('parts.update', $part) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-input-label value="Référence (SKU)" />
            <p class="mt-1 px-3 py-2 rounded-[9px] bg-paper border border-line font-mono text-[13px] text-ink-body">{{ $part->sku }}</p>
        </div>

        <div>
            <x-input-label for="name" value="Nom de la pièce" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $part->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="unit" value="Unité (ex. : unité, mètre, litre)" />
                <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', $part->unit)" />
            </div>
            <div>
                <x-input-label for="reorder_threshold" value="Seuil d'alerte" />
                <x-text-input id="reorder_threshold" name="reorder_threshold" type="number" min="0" class="mt-1 block w-full" :value="old('reorder_threshold', $part->reorder_threshold)" required />
                <x-input-error :messages="$errors->get('reorder_threshold')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="unit_cost" value="Coût unitaire en FCFA" />
            <x-text-input id="unit_cost" name="unit_cost" type="number" step="1" min="0" class="mt-1 block w-full" :value="old('unit_cost', $part->unit_cost)" />
        </div>

        <label class="flex items-center gap-2.5 text-[13.5px]">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $part->is_active))>
            Pièce au catalogue (proposée aux réservations et aux commandes)
        </label>

        <div class="flex justify-end gap-3">
            <a href="{{ route('parts.show', $part) }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</x-form-page>
