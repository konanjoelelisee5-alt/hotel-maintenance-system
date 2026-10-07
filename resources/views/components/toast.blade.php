{{-- Message de réussite en bas de l'écran (layouts.sheet) : messages flash du serveur (success, status), ou
     window.dispatchEvent(new CustomEvent('hk-toast', { detail: 'Texte' })).
     offset : position verticale (au-dessus de la barre du bas sur téléphone). --}}
@props(['offset' => 'bottom-5'])

@php
    // Codes de Breeze (profil, connexion) traduits.
    $statusMessages = [
        'profile-updated' => 'Profil enregistré.',
        'password-updated' => 'Mot de passe modifié.',
        'verification-link-sent' => 'Lien de vérification envoyé.',
    ];
    $toast = session('success') ?? (session('status') ? ($statusMessages[session('status')] ?? session('status')) : null);
@endphp

{{-- Le texte du serveur n'est écrit qu'une fois (dans le span) : Alpine le relit au démarrage. --}}
<div x-data="{ message: null, timer: null,
               show(text) { this.message = text; clearTimeout(this.timer); this.timer = setTimeout(() => this.message = null, 4500); } }"
     x-init="const initial = $refs.text.textContent.trim(); if (initial) show(initial)" @hk-toast.window="show($event.detail)"
     class="fixed inset-x-0 z-[70] flex justify-center px-4 pointer-events-none print:hidden {{ $offset }}"
     role="status" aria-live="polite">
    <div x-show="message" x-transition.opacity.duration.200ms @if (! $toast) x-cloak @endif
         class="pointer-events-auto max-w-[420px] w-full flex items-center gap-3 pl-4 pr-2 py-2.5 rounded-xl bg-navy text-white shadow-[0_16px_36px_-12px_rgba(14,33,54,.6)]">
        <span class="w-7 h-7 rounded-full bg-green flex items-center justify-center flex-shrink-0"><x-hk.icon name="check" :size="16" /></span>
        <span class="flex-1 text-[13.5px] font-medium" x-ref="text" x-text="message">{{ $toast }}</span>
        <button type="button" @click="message = null" class="w-9 h-9 rounded-lg flex items-center justify-center text-side-soft hover:bg-white/10" aria-label="Fermer">
            <x-hk.icon name="x" :size="16" />
        </button>
    </div>
</div>
