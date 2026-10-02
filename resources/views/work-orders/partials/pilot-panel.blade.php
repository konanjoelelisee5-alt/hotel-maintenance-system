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
    // Formulaire de motif à rouvrir après une erreur de saisie.
    $openReason = $errors->has('reason') ? old('_action') : null;
@endphp

<x-panel title="Pilotage de l'OT" icon="flag" class="border-navy/20">
    <x-slot:badge>
        <span class="hidden sm:inline text-[12px] text-ink-grey truncate">
            {{ $assignee ? 'Intervenant : '.$assignee->name : 'Aucun intervenant' }}
            @if ($workOrder->scheduled_at) · planifié le {{ $workOrder->scheduled_at->format('d/m à H\hi') }} @endif
        </span>
    </x-slot:badge>

    <div class="flex flex-col gap-4" x-data="{ reason: @js($openReason) }">
        {{-- Prochaine étape : une seule action mise en avant, selon l'état. --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between p-4 rounded-[12px] border border-gold/30 bg-[#FBF6EC]">
            <div class="flex gap-3 min-w-0">
                <span class="w-9 h-9 flex-shrink-0 rounded-full bg-white border border-gold/30 flex items-center justify-center text-gold">
                    <x-nav-icon name="info" class="w-[18px] h-[18px]" />
                </span>
                <div class="text-[13.5px] leading-snug text-[#3d3a33] min-w-0">
                    <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-0.5">Prochaine étape</div>
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
                                <span class="block text-[12px] text-amber mt-1">Vous avez réalisé cette intervention : le contrôle revient à un autre responsable.</span>
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
            </div>

            <div class="flex-shrink-0">
                @if (in_array($status, ['ouvert', 'en_cours'], true) && ! $assignee)
                    <a href="{{ route('work-orders.schedule', $workOrder) }}" class="btn btn-primary w-full sm:w-auto"><x-nav-icon name="user" /> Affecter et planifier</a>
                @elseif ($status === 'en_attente' && auth()->user()->can('resume', $workOrder))
                    <form method="POST" action="{{ route('work-orders.resume', $workOrder) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full sm:w-auto"><x-nav-icon name="play" /> Relancer</button>
                    </form>
                @elseif ($status === 'resolu' && $canReview)
                    <a href="{{ route('quality-controls.create', $workOrder) }}" class="btn btn-primary w-full sm:w-auto"><x-nav-icon name="shield" /> Faire le contrôle qualité</a>
                @endif
            </div>
        </div>

        {{-- Actions d'arbitre (seulement celles possibles dans l'état actuel). --}}
        @unless (in_array($status, ['ferme', 'annule'], true))
            <div class="flex flex-wrap gap-2">
                @if ($assignee && in_array($status, ['ouvert', 'en_cours', 'en_attente', 'rejete'], true))
                    <a href="{{ route('work-orders.schedule', $workOrder) }}" class="btn btn-secondary"><x-nav-icon name="swap" /> Réaffecter / replanifier</a>
                @endif
                @can('update', $workOrder)
                    <a href="{{ route('work-orders.edit', $workOrder) }}" data-modal class="btn btn-secondary"><x-nav-icon name="pencil" /> Requalifier</a>
                @endcan
                @can('takeOver', $workOrder)
                    <form method="POST" action="{{ route('work-orders.take-over', $workOrder) }}"
                          onsubmit="return confirm('Vous charger vous-même de cette réparation ? Vous en deviendrez l\'intervenant, et le contrôle qualité sera fait par quelqu\'un d\'autre.');">
                        @csrf
                        <button type="submit" class="btn btn-secondary"><x-nav-icon name="hand" /> Je m'en charge</button>
                    </form>
                @endcan
                @can('suspend', $workOrder)
                    <button type="button" class="btn btn-secondary" :class="{ 'ring-2 ring-navy/15 border-navy': reason === 'suspend' }"
                            @click="reason = reason === 'suspend' ? null : 'suspend'" :aria-expanded="reason === 'suspend'">
                        <x-nav-icon name="pause" /> Mettre en attente…
                    </button>
                @endcan
                @can('cancel', $workOrder)
                    <button type="button" class="btn btn-danger" :class="{ 'ring-2 ring-red/15': reason === 'cancel' }"
                            @click="reason = reason === 'cancel' ? null : 'cancel'" :aria-expanded="reason === 'cancel'">
                        <x-nav-icon name="x" /> Annuler l'OT…
                    </button>
                @endcan
            </div>

            {{-- Motifs : formulaire déplié sous les boutons, un seul à la fois. --}}
            @can('suspend', $workOrder)
                <form method="POST" action="{{ route('work-orders.suspend', $workOrder) }}" x-show="reason === 'suspend'" x-cloak
                      class="flex flex-col sm:flex-row gap-2 p-3.5 rounded-[12px] bg-paper border border-line">
                    @csrf
                    <input type="hidden" name="_action" value="suspend">
                    <input type="text" name="reason" required maxlength="500" aria-label="Motif de la mise en attente"
                           placeholder="Motif : pièce commandée, chambre occupée, fournisseur…" value="{{ old('_action') === 'suspend' ? old('reason') : '' }}">
                    <button type="submit" class="btn btn-primary">Confirmer la mise en attente</button>
                </form>
            @endcan
            @can('cancel', $workOrder)
                <form method="POST" action="{{ route('work-orders.cancel', $workOrder) }}" x-show="reason === 'cancel'" x-cloak
                      class="flex flex-col sm:flex-row gap-2 p-3.5 rounded-[12px] bg-[#FDF3F2] border border-red/20">
                    @csrf
                    <input type="hidden" name="_action" value="cancel">
                    <input type="text" name="reason" required maxlength="500" aria-label="Motif de l'annulation"
                           placeholder="Motif : doublon de l'OT-…, fausse alerte…" value="{{ old('_action') === 'cancel' ? old('reason') : '' }}">
                    <button type="submit" class="btn btn-danger-solid">Annuler l'OT</button>
                </form>
            @endcan
            <x-input-error :messages="$errors->get('reason')" />
        @endunless
    </div>
</x-panel>
