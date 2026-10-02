@props(['title', 'card' => true, 'crumb' => null, 'icon' => null, 'subtitle' => null, 'back' => null])

{{-- Formulaire affiché en page complète, ou dans une fenêtre quand il est ouvert par un
     lien [data-modal] (en-tête X-Modal, cf. resources/js/modal.js). Le formulaire est le
     même dans les deux cas ; ses liens « Annuler » marqués data-modal-close ferment la
     fenêtre au lieu de changer de page. crumb / icon / subtitle habillent l'en-tête
     de la fenêtre ; en page complète, crumb et title forment l'en-tête commun (bureau et
     téléphone), et back ajoute la flèche retour de la barre mobile. --}}
@if (request()->hasHeader(\App\Http\Middleware\HandleModalRequests::HEADER))
    <x-modal-panel :title="$title" :crumb="$crumb" :icon="$icon" :subtitle="$subtitle">
        <div class="space-y-6">
            {{ $slot }}
        </div>
    </x-modal-panel>
@else
    <x-app-layout :crumb="$crumb ?? ''" :page-title="$title" :back-route="$back">
        <div class="w-full max-w-2xl space-y-6">
            @if ($subtitle)
                <p class="text-[13.5px] text-ink-grey leading-relaxed">{{ $subtitle }}</p>
            @endif
            @if ($card)
                <div class="ui-form bg-white border border-line rounded-xl p-6">
                    {{ $slot }}
                </div>
            @else
                {{ $slot }}
            @endif
        </div>
    </x-app-layout>
@endif
