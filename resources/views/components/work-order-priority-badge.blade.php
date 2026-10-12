@props(['priority'])

{{-- Couleur choisie par l'admin (Paramètres › Priorités) ; texte foncé si besoin pour rester lisible. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold']) }}
      style="background-color: {{ $priority->color }}18; color: {{ \App\Support\Swatch::readableOnTint($priority->color) }};">
    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $priority->color }};"></span>
    {{ $priority->label }}
</span>
