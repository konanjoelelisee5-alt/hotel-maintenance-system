<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Nouveau plan de maintenance préventive') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('maintenance-plans.store') }}" class="space-y-6">
                    @csrf
                    @include('maintenance-plans._fields')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('maintenance-plans.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Créer le plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
