{{-- Avancement déclaré par l'intervenant assigné (cf. UpdateWorkOrderStatusRequest).
     Les superviseurs ont leur propre panneau « Pilotage ». --}}
@php $current = old('status', in_array($workOrder->status, ['en_cours', 'en_attente', 'resolu'], true) ? $workOrder->status : 'en_cours'); @endphp

<x-panel id="status-form" title="Avancement de mon intervention" icon="status">
    <form method="POST" action="{{ route('work-orders.status.update', $workOrder) }}" class="flex flex-col gap-3">
        @csrf
        @method('PATCH')

        {{-- Choix en pastilles (style .ui-form). --}}
        <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Nouveau statut">
            @foreach (['en_cours' => 'En cours', 'en_attente' => 'En attente', 'resolu' => 'Résolu'] as $value => $label)
                <label class="!flex justify-center !px-2">
                    <input type="radio" name="status" value="{{ $value }}" @checked($current === $value) required>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <input type="text" name="note" value="{{ old('note') }}" aria-label="Note" placeholder="Note (obligatoire si « En attente ») : pièce manquante, client présent…">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </div>
        <x-input-error :messages="$errors->get('status')" />
        <x-input-error :messages="$errors->get('note')" />
    </form>
</x-panel>
