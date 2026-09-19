{{--
    Carte générique réutilisable (fond clair, bordure fine, coins arrondis).
    Utilisée pour les chips metrics, cartes OT, panneaux d'info, notifications...
--}}
<div {{ $attributes->merge(['class' => 'bg-white border border-line rounded-xl']) }}>
    {{ $slot }}
</div>
