{{-- Panneau « Pilotage » : la fiche OT vue par un superviseur (admin, manager).
     Il ne travaille pas sur l'OT, il le fait avancer : une « prochaine étape »
     mise en avant selon l'état, puis ses actions d'arbitre. Le technicien, lui,
     a son chrono, son avancement et son rapport (cf. show.blade.php). --}}
@php
    $status = $workOrder->status;
    $assignee = $workOrder->assignee;
    $lastHold = $status === 'en_attente'
        ? $workOrder->statusHistories->where('new_status', 'en_attente')->sortByDesc('id')->first()
        : null;
    $canReview = auth()->user()->can('reviewQuality', $workOrder);
    $btn = 'inline-flex items-center px-[15px] py-[9px] rounded-[9px] text-[13px] font-semibold';
    $primary = $btn.' bg-navy text-white';
    $secondary = $btn.' border border-line bg-white text-[#3d3a33]';
@endphp

<div class="bg-white rounded-xl border-2 border-navy/15">
    <div class="px-5 py-4 border-b border-line-soft flex items-center justify-between gap-3">
        <h3 class="font-semibold text-navy-900 text-sm">Pilotage de l'OT</h3>
        <span class="text-xs text-ink-grey">
            {{ $assignee ? 'Intervenant : '.$assignee->name : 'Aucun intervenant' }}
            @if ($workOrder->scheduled_at) · planifié le {{ $workOrder->scheduled_at->format('d/m à H\hi') }} @endif
        </span>
    </div>

    <div class="p-5 space-y-4">
        {{-- Prochaine étape : une seule action mise en avant, selon l'état. --}}
        <div class="p-4 rounded-lg bg-paper border border-line-soft flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
            <div class="text-sm">
                <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-1">Prochaine étape</div>
                @switch(true)
                    @case(in_array($status, ['ouvert', 'en_cours'], true) && ! $assignee)
                        Personne n'est sur cet OT : <strong>affectez un technicien</strong> et planifiez son passage.
                        @break
                    @case($status === 'ouvert')
                        {{ $assignee->name }} doit démarrer l'intervention. Suivez son passage.
                        @break
                    @case($status === 'en_cours')
                        Intervention en cours par {{ $assignee->name }} ({{ $workOrder->total_worked_minutes }} min saisies).
                        @break
                    @case($status === 'en_attente')
                        En attente{{ $lastHold?->note ? ' — « '.$lastHold->note.' »' : '' }}.
                        <strong>Relancez</strong> quand le blocage est levé.
                        @break
                    @case($status === 'resolu')
                        Réparation déclarée terminée : <strong>faites le contrôle qualité</strong> pour fermer l'OT.
                        @unless ($canReview)
                            <span class="block text-xs text-amber-800 mt-1">Vous avez réalisé cette intervention : le contrôle revient à un autre responsable.</span>
                        @endunless
                        @break
                    @case($status === 'rejete')
                        Contrôle qualité refusé : correction en cours par {{ $assignee?->name ?? "l'intervenant" }}.
                        @break
                    @case($status === 'ferme')
                        OT fermé après contrôle qualité. Rien à faire.
                        @break
                    @case($status === 'annule')
                        OT annulé. Il reste consultable dans l'historique.
                        @break
                @endswitch
            </div>

            <div class="flex-shrink-0">
                @if (in_array($status, ['ouvert', 'en_cours'], true) && ! $assignee)
                    <a href="{{ route('work-orders.schedule', $workOrder) }}" class="{{ $primary }}">👷 Affecter et planifier</a>
                @elseif ($status === 'en_attente' && auth()->user()->can('resume', $workOrder))
                    <form method="POST" action="{{ route('work-orders.resume', $workOrder) }}">
                        @csrf
                        <button type="submit" class="{{ $primary }}">▶ Relancer</button>
                    </form>
                @elseif ($status === 'resolu' && $canReview)
                    <a href="{{ route('quality-controls.create', $workOrder) }}" class="{{ $primary }}">🔍 Faire le contrôle qualité</a>
                @endif
            </div>
        </div>

        {{-- Autres actions d'arbitre (seulement celles possibles dans l'état actuel). --}}
        @unless (in_array($status, ['ferme', 'annule'], true))
            <div class="flex flex-wrap gap-2">
                @if ($assignee && in_array($status, ['ouvert', 'en_cours', 'en_attente', 'rejete'], true))
                    <a href="{{ route('work-orders.schedule', $workOrder) }}" class="{{ $secondary }}">🔁 Réaffecter / replanifier</a>
                @endif
                @can('update', $workOrder)
                    <a href="{{ route('work-orders.edit', $workOrder) }}" class="{{ $secondary }}">✏️ Requalifier</a>
                @endcan
                @can('takeOver', $workOrder)
                    <form method="POST" action="{{ route('work-orders.take-over', $workOrder) }}"
                          onsubmit="return confirm('Vous charger vous-même de cette réparation ? Vous en deviendrez l\'intervenant, et le contrôle qualité sera fait par quelqu\'un d\'autre.');">
                        @csrf
                        <button type="submit" class="{{ $secondary }}">🙋 Je m'en charge</button>
                    </form>
                @endcan
            </div>

            {{-- Actions qui exigent un motif : formulaire déplié sur place. --}}
            <div class="space-y-2">
                @can('suspend', $workOrder)
                    <details class="group rounded-lg border border-line-soft" @if ($errors->has('reason') && old('_action') === 'suspend') open @endif>
                        <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-[#3d3a33]">⏸ Mettre en attente…</summary>
                        <form method="POST" action="{{ route('work-orders.suspend', $workOrder) }}" class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                            @csrf
                            <input type="hidden" name="_action" value="suspend">
                            <input type="text" name="reason" required maxlength="500" placeholder="Motif : pièce commandée, chambre occupée, fournisseur…"
                                   class="flex-1 border-slate-300 rounded-md shadow-sm text-sm">
                            <button type="submit" class="{{ $secondary }}">Confirmer</button>
                        </form>
                    </details>
                @endcan
                @can('cancel', $workOrder)
                    <details class="group rounded-lg border border-red-100" @if ($errors->has('reason') && old('_action') === 'cancel') open @endif>
                        <summary class="cursor-pointer px-4 py-2.5 text-sm font-semibold text-red-700">❌ Annuler l'OT…</summary>
                        <form method="POST" action="{{ route('work-orders.cancel', $workOrder) }}" class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                            @csrf
                            <input type="hidden" name="_action" value="cancel">
                            <input type="text" name="reason" required maxlength="500" placeholder="Motif : doublon de l'OT-…, fausse alerte…"
                                   class="flex-1 border-slate-300 rounded-md shadow-sm text-sm">
                            <button type="submit" class="{{ $btn }} bg-red-700 text-white">Annuler l'OT</button>
                        </form>
                    </details>
                @endcan
                <x-input-error :messages="$errors->get('reason')" class="mt-1" />
            </div>
        @endunless
    </div>
</div>
