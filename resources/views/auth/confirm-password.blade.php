<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Vous allez modifier des comptes ou des accès. Par sécurité, confirmez votre mot de passe
        (il ne vous sera plus demandé pendant 15 minutes).
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Mot de passe" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autofocus autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-between items-center mt-4">
            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:underline">Annuler</a>
            <x-primary-button>
                Confirmer
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
