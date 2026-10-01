@props(['title', 'card' => true, 'crumb' => null, 'icon' => null, 'subtitle' => null])

{{-- Formulaire affiché en page complète, ou dans une fenêtre quand il est ouvert par un
     lien [data-modal] (en-tête X-Modal, cf. resources/js/modal.js). Le formulaire est le
     même dans les deux cas ; ses liens « Annuler » marqués data-modal-close ferment la
     fenêtre au lieu de changer de page. crumb / icon / subtitle habillent l'en-tête
     de la fenêtre. --}}
@if (request()->hasHeader(\App\Http\Middleware\HandleModalRequests::HEADER))
    <x-modal-panel :title="$title" :crumb="$crumb" :icon="$icon" :subtitle="$subtitle">
        <div class="space-y-6">
            {{ $slot }}
        </div>
    </x-modal-panel>
@else
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
                @if ($card)
                    <div class="bg-white p-6 shadow-sm rounded-lg">
                        {{ $slot }}
                    </div>
                @else
                    {{ $slot }}
                @endif
            </div>
        </div>
    </x-app-layout>
@endif
