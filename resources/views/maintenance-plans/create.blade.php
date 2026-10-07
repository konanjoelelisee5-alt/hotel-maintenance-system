<x-app-layout :crumb="'Exploitation / Maintenance préventive'" :page-title="'Nouveau plan de maintenance'" :back-route="route('maintenance-plans.index')">
    <div>
        <div class="w-full max-w-3xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <form method="POST" action="{{ route('maintenance-plans.store') }}" class="space-y-6">
                    @csrf
                    @include('maintenance-plans._fields')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('maintenance-plans.index') }}" class="btn btn-ghost">Annuler</a>
                        <button type="submit" class="btn btn-primary">Créer le plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
