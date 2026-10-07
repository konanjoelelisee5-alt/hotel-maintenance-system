<x-form-page title="Nouveau lieu" crumb="Patrimoine" icon="building"
             subtitle="Chambre ou espace commun : il pourra être choisi dans les ordres de travail.">
    <form method="POST" action="{{ route('rooms.store') }}" class="space-y-6">
        @csrf
        @include('rooms._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('rooms.index') }}" data-modal-close class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">Créer</button>
        </div>
    </form>
</x-form-page>
