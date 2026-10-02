<x-form-page :title="'Modifier '.$supplier->name" crumb="Stock & achats / Fournisseurs" icon="truck"
             subtitle="Coordonnées utilisées pour passer et suivre les bons de commande."
             :back="route('suppliers.show', $supplier)">
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="name" value="Nom du fournisseur" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $supplier->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="contact_person" value="Personne à contacter" />
                <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person', $supplier->contact_person)" />
            </div>
            <div>
                <x-input-label for="phone" value="Téléphone" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $supplier->phone)" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $supplier->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="address" value="Adresse" />
            <textarea id="address" name="address" rows="2" class="mt-1 block w-full">{{ old('address', $supplier->address) }}</textarea>
        </div>

        <div>
            <x-input-label for="notes" value="Notes" />
            <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full" placeholder="Délais habituels, conditions de paiement…">{{ old('notes', $supplier->notes) }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('suppliers.show', $supplier) }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</x-form-page>
