{{-- Compatibilité Breeze (profil, connexion) : même rendu que <x-button>. --}}
<button {{ $attributes->merge(['type' => 'submit'])->class('btn btn-primary') }}>
    {{ $slot }}
</button>
