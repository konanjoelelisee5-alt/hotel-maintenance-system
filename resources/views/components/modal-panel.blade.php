@props(['title', 'crumb' => null, 'icon' => null, 'subtitle' => null])

{{-- Contenu d'une fenêtre (modale) chargée par resources/js/modal.js, dans le style des
     pages de la refonte : en-tête « rubrique / titre » comme l'en-tête des pages, champs
     et boutons mis en forme par la classe .modal-form (resources/css/app.css).
     Les éléments [data-modal-close] ferment la fenêtre. --}}
<div class="flex flex-col min-h-full">
    <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-line">
        {{-- Poignée : sur téléphone, la fenêtre monte du bas de l'écran. --}}
        <div class="sm:hidden flex justify-center pt-2.5" aria-hidden="true">
            <span class="w-10 h-1 rounded-full bg-line"></span>
        </div>
        <div class="flex items-start gap-3.5 px-6 pt-4 pb-4 sm:pt-5">
            @if ($icon)
                <span class="w-10 h-10 flex-shrink-0 rounded-[10px] bg-paper border border-line flex items-center justify-center text-gold">
                    <x-nav-icon :name="$icon" class="w-5 h-5" />
                </span>
            @endif
            <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                @if ($crumb)
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">{{ $crumb }}</div>
                @endif
                <h2 class="m-0 text-[19px] leading-tight font-semibold tracking-tight text-navy" id="remote-modal-title">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-[13px] text-ink-muted leading-snug mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" data-modal-close class="w-9 h-9 -mr-1.5 flex-shrink-0 rounded-[9px] flex items-center justify-center text-ink-grey hover:text-navy hover:bg-line-soft transition">
                <x-nav-icon name="close" class="w-4 h-4" />
                <span class="sr-only">Fermer</span>
            </button>
        </div>
    </div>

    <div class="modal-form flex-1 px-6 pt-6">
        {{ $slot }}
    </div>
</div>
