<x-form-page title="Nouvel équipement" crumb="Patrimoine" icon="wrench"
             subtitle="Appareil suivi par la maintenance : son historique de pannes s'y rattachera.">
    <form method="POST" action="{{ route('equipment.store') }}" class="space-y-6">
        @csrf
        @include('equipment._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('equipment.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
