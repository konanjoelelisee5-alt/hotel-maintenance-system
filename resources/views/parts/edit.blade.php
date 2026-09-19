<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Modifier') }} {{ $part->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('parts.update', $part) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label value="Référence (SKU)" />
                        <p class="mt-1 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-600 font-mono">{{ $part->sku }}</p>
                    </div>

                    <div>
                        <x-input-label for="name" value="Nom de la pièce" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $part->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="unit" value="Unité" />
                            <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" :value="old('unit', $part->unit)" />
                        </div>
                        <div>
                            <x-input-label for="reorder_threshold" value="Seuil d'alerte" />
                            <x-text-input id="reorder_threshold" name="reorder_threshold" type="number" min="0" class="mt-1 block w-full" :value="old('reorder_threshold', $part->reorder_threshold)" required />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="unit_cost" value="Coût unitaire" />
                        <x-text-input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('unit_cost', $part->unit_cost)" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($part->is_active) class="rounded border-gray-300">
                            Pièce active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('parts.show', $part) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>