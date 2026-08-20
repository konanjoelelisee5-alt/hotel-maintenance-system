<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouvel utilisateur') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nom complet" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="E-mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role" value="Rôle" />
                        <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrateur</option>
                            <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                            <option value="technicien" @selected(old('role') === 'technicien')>Technicien</option>
                            <option value="housekeeping" @selected(old('role') === 'housekeeping')>Housekeeping</option>
                            <option value="reception" @selected(old('role') === 'reception')>Réception</option>
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Mot de passe" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>