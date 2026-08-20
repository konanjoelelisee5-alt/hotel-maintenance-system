<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouveau fournisseur') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('suppliers.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nom du fournisseur" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="contact_person" value="Personne à contacter" />
                            <x-text-input id="contact_person" name="contact_person" type="text" class="mt-1 block w-full" :value="old('contact_person')" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Téléphone" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone')" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" value="E-mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Adresse" />
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <x-input-label for="notes" value="Notes" />
                        <textarea id="notes" name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('suppliers.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>