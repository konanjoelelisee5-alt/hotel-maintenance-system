{{-- Réinitialisation du mot de passe d'un compte. $compact : version fenêtre, une ligne
     en tête (texte à gauche, bouton à droite) au-dessus du formulaire, pour ne pas finir
     sous le pied collant de la fenêtre. --}}
@if ($compact)
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between p-4 rounded-[10px] bg-paper border border-line-soft">
        <div class="min-w-0">
            <h3>Mot de passe</h3>
            <p class="text-[12.5px] text-[#6C6658] mt-0.5 leading-snug">
                Génère un mot de passe provisoire, à changer à sa prochaine connexion.
            </p>
        </div>
        <form method="POST" action="{{ route('users.password.reset', $user) }}" class="flex-shrink-0"
              data-confirm="L’ancien mot de passe ne fonctionnera plus ; un mot de passe provisoire sera affiché une seule fois." data-confirm-title="Réinitialiser le mot de passe ?" data-confirm-label="Réinitialiser">
            @csrf
            <button type="submit">Réinitialiser</button>
        </form>
    </div>
@else
    <div class="bg-white p-6 shadow-sm rounded-lg">
        <h3 class="font-semibold text-gray-800">Mot de passe</h3>
        <p class="text-sm text-gray-500 mt-1">
            Génère un mot de passe temporaire à transmettre à {{ $user->name }}.
            Il devra le remplacer dès sa prochaine connexion.
        </p>
        <form method="POST" action="{{ route('users.password.reset', $user) }}" class="mt-4"
              data-confirm="L’ancien mot de passe ne fonctionnera plus ; un mot de passe provisoire sera affiché une seule fois." data-confirm-title="Réinitialiser le mot de passe ?" data-confirm-label="Réinitialiser">
            @csrf
            <button type="submit" class="px-4 py-2 bg-white border border-gray-300 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-50">
                Réinitialiser le mot de passe
            </button>
        </form>
    </div>
@endif
