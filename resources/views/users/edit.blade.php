<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Modifier') }} {{ $user->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nom complet" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="E-mail" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role" value="Rôle" />
                        <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required
                            @if ($user->id === auth()->id()) disabled @endif>
                            <option value="admin" @selected(old('role', $user->role->value) === 'admin')>Administrateur</option>
                            <option value="manager" @selected(old('role', $user->role->value) === 'manager')>Manager</option>
                            <option value="technicien" @selected(old('role', $user->role->value) === 'technicien')>Technicien</option>
                            <option value="housekeeping" @selected(old('role', $user->role->value) === 'housekeeping')>Housekeeping</option>
                            <option value="reception" @selected(old('role', $user->role->value) === 'reception')>Réception</option>
                        </select>
                        @if ($user->id === auth()->id())
                            <input type="hidden" name="role" value="{{ $user->role->value }}">
                            <p class="text-xs text-gray-500 mt-1">Vous ne pouvez pas modifier votre propre rôle.</p>
                        @endif
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active)
                                @if ($user->id === auth()->id()) disabled @endif
                                class="rounded border-gray-300">
                            Compte actif
                        </label>
                        @if ($user->id === auth()->id())
                            <input type="hidden" name="is_active" value="1">
                        @endif
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>