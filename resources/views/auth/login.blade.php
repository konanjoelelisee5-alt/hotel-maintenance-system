<x-guest-layout>
    <div class="mb-6 text-center lg:text-left">
        <h2 class="text-xl font-semibold text-navy">Connexion</h2>
        <p class="text-sm text-ink-grey mt-1">Accédez à votre espace de gestion technique</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-[14px] font-medium text-[#26496B] mb-1.5">Adresse e-mail</label>
            <input id="email" type="email" name="email" placeholder="prenom.nom@hotel-president.fr"
                class="block w-full h-[48px] px-4 rounded-lg border border-line bg-white text-[14px] placeholder:text-[#A09A8C] focus:border-navy focus:ring-navy"
                value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-[14px] font-medium text-[#26496B] mb-1.5">Mot de passe</label>
            <input id="password" type="password" name="password" placeholder="••••••••"
                class="block w-full h-[48px] px-4 rounded-lg border border-line bg-white text-[14px] placeholder:text-[#A09A8C] focus:border-navy focus:ring-navy"
                required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center min-h-[44px]">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-line text-navy focus:ring-navy">
                <span class="ms-2 text-sm text-[#26496B]">Rester connecté sur cet appareil</span>
            </label>
        </div>

        <button type="submit"
            class="w-full inline-flex items-center justify-center h-[50px] bg-navy rounded-lg font-semibold text-[15px] text-white hover:bg-navy-light active:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-navy transition">
            Se connecter
        </button>

        @if (Route::has('password.request'))
            <p class="text-center">
                <a class="text-[13px] text-[#26496B] hover:underline" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            </p>
        @endif
    </form>

    <p class="mt-6 text-center text-xs text-ink-grey">
        Accès réservé au personnel autorisé de l'Hôtel Président.<br>
        Contactez un administrateur pour obtenir un compte.
    </p>
</x-guest-layout>
