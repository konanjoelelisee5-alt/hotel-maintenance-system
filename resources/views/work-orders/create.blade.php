@if (request()->hasHeader(\App\Http\Middleware\HandleModalRequests::HEADER))
    {{-- Ouvert depuis un bouton « + Nouvel ordre » : seulement le contenu de la fenêtre. --}}
    <x-modal-panel title="Nouvel ordre de travail">
        @include('work-orders.partials.create-form', ['inModal' => true])
    </x-modal-panel>
@else
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Nouvel ordre de travail') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    @include('work-orders.partials.create-form')
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
