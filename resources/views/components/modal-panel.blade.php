@props(['title'])

{{-- Contenu d'une fenêtre (modale) chargée par resources/js/modal.js : titre, bouton
     fermer, puis le contenu. Les éléments [data-modal-close] ferment la fenêtre. --}}
<div class="flex flex-col">
    <div class="sticky top-0 z-10 flex items-center justify-between gap-3 px-5 py-4 bg-white border-b border-line">
        <h2 class="text-[17px] font-semibold text-navy" id="remote-modal-title">{{ $title }}</h2>
        <button type="button" data-modal-close class="w-9 h-9 flex-shrink-0 rounded-lg border border-line flex items-center justify-center text-ink-grey hover:text-navy">
            <x-nav-icon name="close" class="w-4 h-4" />
            <span class="sr-only">Fermer</span>
        </button>
    </div>
    <div class="px-5 pt-5">
        {{ $slot }}
    </div>
</div>
