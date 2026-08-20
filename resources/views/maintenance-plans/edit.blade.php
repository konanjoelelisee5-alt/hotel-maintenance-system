<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Modifier le plan') }} — {{ $maintenancePlan->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('maintenance-plans.update', $maintenancePlan) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    @include('maintenance-plans._fields', ['plan' => $maintenancePlan])

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('maintenance-plans.show', $maintenancePlan) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
