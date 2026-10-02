@if (request()->hasHeader(\App\Http\Middleware\HandleModalRequests::HEADER))
    {{-- Ouvert depuis un bouton « + Nouvel ordre » : seulement le contenu de la fenêtre. --}}
    <x-modal-panel title="Nouvel ordre de travail" crumb="Ordres de travail" icon="clipboard"
                   subtitle="Décrivez le problème et où il se trouve : il part aussitôt vers la maintenance.">

        @include('work-orders.partials.create-form', ['inModal' => true])
    </x-modal-panel>
@else
    <x-app-layout :crumb="'Exploitation / Ordres de travail'" :page-title="'Nouvel ordre de travail'" :back-route="route('work-orders.index')">
        <div>
            <div class="w-full max-w-3xl">
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    @include('work-orders.partials.create-form')
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
