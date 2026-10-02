<x-app-layout :crumb="'Exploitation / Maintenance préventive'" :page-title="'Modifier « '.$maintenancePlan->name.' »'" :back-route="route('maintenance-plans.show', $maintenancePlan)">
    <div>
        <div class="w-full max-w-3xl">
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
