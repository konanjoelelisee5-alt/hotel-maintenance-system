{{-- Bouton unique de l'application (classes .btn de resources/css/app.css).

     <x-button>Enregistrer</x-button>                                   bouton d'envoi principal
     <x-button variant="secondary" type="button" icon="plus">…</x-button>
     <x-button href="{{ route(...) }}" variant="ghost" size="sm">…</x-button>   lien au même aspect

     variant : primary | secondary | gold | success | danger | danger-solid | ghost
     size    : sm | md | lg
     États : normal, appuyé, désactivé (disabled), et data-state="loading | success | error".
     Un bouton d'envoi passe seul en « loading » pendant l'envoi et ne peut pas être
     cliqué deux fois (resources/js/ui.js). --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'submit',
    'href' => null,
    'icon' => null,
    'disabled' => false,
])

@php
    $classes = 'btn btn-'.$variant.($size === 'md' ? '' : ' btn-'.$size);
@endphp

@if ($href)
    <a href="{{ $disabled ? '#' : $href }}" @if ($disabled) aria-disabled="true" tabindex="-1" @endif {{ $attributes->class($classes) }}>
        @if ($icon)<x-nav-icon :name="$icon" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>
        @if ($icon)<x-nav-icon :name="$icon" />@endif
        {{ $slot }}
    </button>
@endif
