<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-navy-900">Connexion</h2>
        <p class="text-sm text-slate-500 mt-1">Accédez à votre espace de gestion technique</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">E-mail</label>
            <input id="email" type="email" name="email" :value="old('email')"
                class="block w-full rounded-md border-slate-300 shadow-sm focus:border-navy-500 focus:ring-navy-500 text-sm"
                value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe</label>
            <input id="password" type="password" name="password"
                class="block w-full rounded-md border-slate-300 shadow-sm focus:border-navy-500 focus:ring-navy-500 text-sm"
                required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-slate-300 text-navy-700 shadow-sm focus:ring-navy-500">
                <span class="ms-2 text-sm text-slate-600">Se souvenir de moi</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-navy-700 hover:text-navy-900 hover:underline" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        <button type="submit"
            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-navy-800 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-navy-700 focus:bg-navy-700 active:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy-500 transition ease-in-out duration-150">
            Se connecter
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400">
        Accès réservé au personnel autorisé de l'Hôtel Président.<br>
        Contactez un administrateur pour obtenir un compte.
    </p>
</x-guest-layout>