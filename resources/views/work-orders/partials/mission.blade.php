{{-- L'intervention du technicien en 4 étapes, comme le signalement HK (x-wizard) : une étape
     par écran, « Continuer » grisé tant qu'elle n'est pas complète (la raison s'affiche
     dessous), l'étape dans l'adresse (#etape-2 : le bouton retour du téléphone recule d'une
     étape). Ordre réel du travail :
       ① Préparer              : déjà réparé ici ?, « Avez-vous besoin de pièces ? » ;
                                 Continuer = « je suis sur place », le chrono démarre ;
       ② Sur place             : le chrono, « La panne est-elle réparée ? » — Oui (Continuer
                                 arrête le chrono) ou « Non, je suis bloqué » + pourquoi
                                 (pièce manquante → demande au manager ; client, accès,
                                 prestataire → en attente avec le motif, chrono arrêté) ;
       ③ Pièces utilisées      : chaque pièce réservée, sortie ou rendue au magasin ;
       ④ Rapport et signature  : le rapport ; signé, l'OT passe en « Réparé ».
     Contrairement au HK, des gestes partent au serveur en cours de route (chrono, pièces) :
     l'étape de départ se déduit de l'état de l'OT, et chaque envoi revient sur son étape.
     Ouvrir une étape depuis ailleurs : $dispatch('mission-step', n). --}}
@php
    $status = $workOrder->status;
    $session = $workOrder->activeSession();
    $hasTime = $workOrder->interventionSessions->isNotEmpty();
    $repaired = $status === 'resolu';
    $started = $hasTime || ! in_array($status, ['ouvert'], true);
    $previous = $workOrder->previousRepairs();
    $reserved = $workOrder->partReservations->where('status', '!=', 'annulee');
    $toWithdraw = $workOrder->partReservations->where('status', 'reservee');
    $lastHold = $status === 'en_attente'
        ? $workOrder->statusHistories->where('new_status', 'en_attente')->sortByDesc('id')->first()
        : null;
    $corrections = $workOrder->correctionRequests->where('status', 'ouverte');
    $report = $workOrder->interventionReport;

    $names = ['Préparer', 'Sur place', 'Pièces utilisées', 'Rapport et signature'];
    // Étape de départ d'après l'état ; une erreur du serveur rouvre l'étape concernée.
    $step = match (true) {
        $repaired, $errors->hasAny(['work_performed', 'parts_used', 'recommendations', 'signed_by_name', 'signature']) => 4,
        ! $started => 1,
        default => 2,
    };
    // Plus loin que l'état ne le permet, on ne va pas (adresse #etape-4 tapée à la main…).
    $reachable = match (true) { $repaired => 4, $started => 4, default => 1 };
    $needsParts = $reserved->isNotEmpty() || $workOrder->partRequests->isNotEmpty() ? true : null;

    $reasons = [
        ['piece', 'Il manque une pièce', 'briefcase'],
        ['client', 'Client dans la chambre', 'user'],
        ['acces', 'Accès impossible', 'lock'],
        ['prestataire', 'Il faut un prestataire', 'wrench'],
        ['autre', 'Autre raison', 'help'],
    ];
    $choice = 'flex-1 min-h-[64px] px-4 rounded-2xl border-2 text-[15px] font-semibold flex items-center justify-center gap-2 transition-colors';
    $question = 'm-0 text-[17px] font-bold text-ink-deep';
@endphp

<section id="mission" aria-label="Intervention en 4 étapes" class="flex flex-col gap-4"
         x-data="{
            step: {{ $step }},
            reachable: {{ $reachable }},
            needParts: @js($needsParts),
            repaired: @js($repaired ? true : null),
            blocked: false,
            reason: null,
            workDone: @js(filled(old('work_performed', $report?->work_performed))),
            started: @js($started),
            running: @js($session !== null),
            canRepair: @js(in_array($status, ['en_cours', 'rejete'], true) || ($status === 'ouvert' && $hasTime)),
            toWithdraw: {{ $toWithdraw->count() }},
            get stepValid() {
                return [
                    this.needParts !== null,
                    this.canRepair && this.repaired === true,
                    this.toWithdraw === 0,
                    false,
                ][this.step - 1];
            },
            get hint() {
                if (this.step === 1) return 'Répondez : avez-vous besoin de pièces ?';
                if (this.step === 2) return this.canRepair ? 'Répondez : la panne est-elle réparée ?' : 'Reprenez la réparation pour continuer.';
                if (this.step === 3) return 'Dites pour chaque pièce si vous l\'avez utilisée.';
                return '';
            },
            go(n) {
                n = Math.max(1, Math.min(n, this.reachable));
                if (n === this.step) return;
                this.step = n;
                history.pushState({ mission: n }, '', '#etape-' + n);
                $nextTick(() => {
                    window.dispatchEvent(new Event('work-order-tab'));
                    $root.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },
            next() {
                if (! this.stepValid) return;
                // Étape 1 : « je suis sur place » = le chrono démarre (serveur), retour à l'étape 2.
                if (this.step === 1 && ! this.started) return $refs.start.requestSubmit();
                // Étape 2 : réparé, le chrono s'arrête (serveur), retour à l'étape 3.
                if (this.step === 2 && this.running) return $refs.stop.requestSubmit();
                this.go(this.step + 1);
            },
            back() { if (this.step > 1) this.go(this.step - 1); },
         }"
         x-init="
            const wanted = Number((location.hash.match(/^#etape-(\d)$/) || [])[1]);
            if (wanted) step = Math.max(1, Math.min(wanted, reachable));
            history.replaceState({ mission: step }, '', '#etape-' + step);
            window.addEventListener('popstate', (e) => { if (e.state?.mission) step = Math.min(e.state.mission, reachable); });
            document.getElementById('work_performed')?.addEventListener('input', (e) => workDone = e.target.value.trim() !== '');
            $watch('step', () => $nextTick(() => window.dispatchEvent(new Event('work-order-tab'))));
            // Zone de signature : sa largeur se lit quand l'étape 4 est affichée.
            window.addEventListener('load', () => window.dispatchEvent(new Event('work-order-tab')));
            // Tout envoi depuis l'assistant revient sur son étape (show.blade.php ajoute #intervention).
            $el.addEventListener('submit', (e) => {
                const action = e.target.getAttribute('action') || '';
                if (! action.includes('#') || action.endsWith('#intervention')) e.target.setAttribute('action', action.replace(/#intervention$/, '') + '#etape-' + step);
            }, true);
         "
         @mission-step.window="tab = 'intervention'; go($event.detail)">

    {{-- Gestes envoyés au serveur par « Continuer » : chacun revient sur l'étape suivante. --}}
    <form x-ref="start" method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}#etape-2" class="hidden">@csrf</form>
    <form x-ref="stop" method="POST" action="{{ route('work-orders.sessions.stop', $workOrder) }}#etape-3" class="hidden">@csrf</form>

    <x-wizard.progress :steps="$names" />

    <div class="bg-white border border-line rounded-2xl p-4 tab:p-5 flex flex-col gap-4">

        {{-- ① Préparer --}}
        <section x-show="step === 1" @if ($step !== 1) x-cloak @endif class="flex flex-col gap-4" aria-labelledby="mission-q1">
            @if ($previous->count() >= 2)
                <div class="flex gap-3 px-3.5 py-3 rounded-xl bg-warn-bg text-[13.5px] text-warn-ink">
                    <x-nav-icon name="history" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span><strong>Déjà réparé {{ $previous->count() }} fois ici.</strong> La panne revient : pensez à remplacer la pièce plutôt qu'à la réparer.</span>
                </div>
            @endif
            @if ($previous->isNotEmpty())
                <details>
                    <summary class="list-none cursor-pointer text-[13.5px] font-semibold text-navy flex items-center gap-2">
                        <x-nav-icon name="history" class="w-4 h-4 text-gold" /> Voir les réparations précédentes ({{ $previous->count() }})
                    </summary>
                    <div class="mt-3">@include('work-orders.partials.previous-repairs')</div>
                </details>
            @endif

            <p id="mission-q1" class="{{ $question }}">Avez-vous besoin de pièces ?</p>
            <div class="flex gap-2.5" role="radiogroup" aria-labelledby="mission-q1">
                <button type="button" role="radio" :aria-checked="(needParts === true).toString()" @click="needParts = true"
                        class="{{ $choice }}" :class="needParts === true ? 'border-navy bg-navy text-white' : 'border-line bg-white text-ink-deep'">
                    <x-nav-icon name="part" class="w-5 h-5" /> Oui
                </button>
                <button type="button" role="radio" :aria-checked="(needParts === false).toString()" @click="needParts = false"
                        class="{{ $choice }}" :class="needParts === false ? 'border-navy bg-navy text-white' : 'border-line bg-white text-ink-deep'">
                    Non
                </button>
            </div>
            <div x-show="needParts === true" @if (! $needsParts) x-cloak @endif>
                @include('work-orders.partials.parts-card')
            </div>
            @unless ($started)
                <p class="m-0 text-[13px] text-ink-muted">« Continuer » quand vous êtes sur place : le chrono démarre.</p>
            @endunless
        </section>

        {{-- ② Sur place --}}
        <section x-show="step === 2" @if ($step !== 2) x-cloak @endif class="flex flex-col gap-4" aria-labelledby="mission-q2">
            @if ($status === 'rejete' && $corrections->isNotEmpty())
                <div class="px-3.5 py-3 rounded-xl bg-danger-bg text-[13.5px] text-danger-ink">
                    <p class="m-0 font-semibold">Le contrôle qualité demande une correction :</p>
                    <ul class="m-0 mt-1 pl-5">
                        @foreach ($corrections as $correction)<li>{{ $correction->description }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if ($status === 'en_attente')
                <div class="flex flex-col gap-3 px-3.5 py-3 rounded-xl bg-warn-bg text-[13.5px] text-warn-ink">
                    <p class="m-0"><strong>Vous êtes bloqué</strong>{{ $lastHold?->note ? ' — « '.$lastHold->note.' »' : '' }}. Le blocage est levé ?</p>
                    <form method="POST" action="{{ route('work-orders.status.update', $workOrder) }}#etape-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="en_cours">
                        <button type="submit" class="btn btn-primary btn-lg w-full"><x-nav-icon name="play" /> Reprendre la réparation</button>
                    </form>
                </div>
            @endif

            @include('work-orders.partials.intervention-tracking')

            @if (in_array($status, ['ouvert', 'en_cours', 'rejete'], true))
                <div x-show="! blocked" class="flex flex-col gap-2.5">
                    <p id="mission-q2" class="{{ $question }}">La panne est-elle réparée ?</p>
                    <div class="flex flex-col sm:flex-row gap-2.5" role="radiogroup" aria-labelledby="mission-q2">
                        <button type="button" role="radio" :aria-checked="(repaired === true).toString()" @click="repaired = true"
                                class="{{ $choice }}" :class="repaired === true ? 'border-green bg-green text-white' : 'border-line bg-white text-ink-deep'">
                            <x-nav-icon name="check" class="w-5 h-5" /> Oui, c'est réparé
                        </button>
                        <button type="button" role="radio" aria-checked="false" @click="repaired = null; blocked = true" class="{{ $choice }} border-line bg-white text-ink-deep">
                            <x-nav-icon name="hand" class="w-5 h-5" /> Non, je suis bloqué
                        </button>
                    </div>
                    <p x-show="repaired === true && running" x-cloak class="m-0 text-[13px] text-ink-muted">« Continuer » arrête le chrono.</p>
                </div>

                {{-- Bloqué : pourquoi ? --}}
                <div x-show="blocked" x-cloak class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="{{ $question }}">Pourquoi êtes-vous bloqué ?</p>
                        <button type="button" @click="blocked = false; reason = null" class="btn btn-ghost btn-sm">Annuler</button>
                    </div>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Motif du blocage">
                        @foreach ($reasons as [$key, $label, $icon])
                            <button type="button" role="radio" :aria-checked="(reason === '{{ $key }}').toString()" @click="reason = '{{ $key }}'"
                                    class="min-h-[76px] px-3 py-2.5 rounded-2xl border-2 flex flex-col items-center justify-center gap-1.5 text-center text-[13px] font-semibold leading-tight transition-colors {{ $loop->last ? 'col-span-2' : '' }}"
                                    :class="reason === '{{ $key }}' ? 'border-navy bg-navy text-white' : 'border-line bg-white text-ink-deep'">
                                <x-hk.icon :name="$icon" :size="22" />
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Pièce manquante : au magasin (étape 1), sinon demande au manager + en attente. --}}
                    <div x-show="reason === 'piece'" x-cloak class="flex flex-col gap-2.5 p-3.5 rounded-xl bg-paper border border-line">
                        <p class="m-0 text-[13.5px] text-ink-strong">Elle est au magasin ?
                            <button type="button" @click="needParts = true; blocked = false; go(1)" class="font-semibold text-blue underline underline-offset-2">Réservez-la</button>.
                            Sinon, demandez-la au manager : l'ordre passe en attente et le chrono s'arrête.</p>
                        <form method="POST" action="{{ route('work-orders.part-requests.store', $workOrder) }}#etape-2" class="flex flex-col gap-2">
                            @csrf
                            <input type="hidden" name="suspend" value="1">
                            <input type="text" name="description" maxlength="200" required aria-label="Pièce manquante" placeholder="Nom, marque, dimension… (ex. : mitigeur Grohe 1/2)">
                            <div class="flex gap-2">
                                <input type="number" name="quantity" min="1" max="999" value="1" aria-label="Quantité" class="!w-24">
                                <button type="submit" class="btn btn-primary flex-1"><x-nav-icon name="send" /> Demander et mettre en attente</button>
                            </div>
                        </form>
                    </div>

                    {{-- Autres motifs : en attente avec le motif (le manager le lit dans son pilotage). --}}
                    <form method="POST" action="{{ route('work-orders.status.update', $workOrder) }}#etape-2" x-show="reason && reason !== 'piece'" x-cloak class="flex flex-col gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="en_attente">
                        <textarea name="note" rows="2" maxlength="1000" required aria-label="Précision sur le blocage"
                                  x-effect="if (reason && reason !== 'piece') $el.value = { client: 'Client dans la chambre : ', acces: 'Accès impossible : ', prestataire: 'Il faut un prestataire : ', autre: '' }[reason]"
                                  placeholder="Précisez (heure de retour du client, qui contacter…)"></textarea>
                        <button type="submit" class="btn btn-primary btn-lg w-full"><x-nav-icon name="pause" /> Mettre en attente</button>
                        <p class="m-0 text-[12px] text-ink-grey">Le chrono s'arrête ; le manager est informé du motif.</p>
                    </form>
                    <x-input-error :messages="$errors->get('note')" />
                </div>
            @endif
        </section>

        {{-- ③ Pièces utilisées --}}
        <section x-show="step === 3" @if ($step !== 3) x-cloak @endif class="flex flex-col gap-3" aria-labelledby="mission-q3">
            <p id="mission-q3" class="{{ $question }}">Avez-vous utilisé ces pièces ?</p>
            @if ($toWithdraw->isEmpty())
                <p class="m-0 text-[14px] text-ink-muted">
                    {{ $reserved->isEmpty() ? 'Aucune pièce réservée pour cet ordre.' : 'Toutes les pièces sont réglées.' }}
                    @foreach ($reserved->where('status', 'sortie') as $r)
                        <span class="mt-1 flex items-center gap-1.5 text-ink-strong"><x-nav-icon name="check" class="w-4 h-4 text-green" /> {{ $r->quantity }} × {{ $r->part->name }} — sortie du magasin</span>
                    @endforeach
                </p>
            @else
                <ul class="m-0 p-0 list-none flex flex-col gap-2">
                    @foreach ($toWithdraw as $reservation)
                        <li class="flex flex-col sm:flex-row sm:items-center gap-2.5 px-3.5 py-3 rounded-xl bg-paper">
                            <span class="flex-1 min-w-0 text-[14px] font-semibold text-navy">{{ $reservation->quantity }} × {{ $reservation->part->name }}</span>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('work-orders.reservations.withdraw', [$workOrder, $reservation]) }}#etape-3" class="flex-1">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-full"><x-nav-icon name="check" /> Utilisée</button>
                                </form>
                                <form method="POST" action="{{ route('work-orders.reservations.cancel', [$workOrder, $reservation]) }}#etape-3" class="flex-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary w-full">Pas utilisée</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="m-0 text-[12px] text-ink-grey">« Pas utilisée » : la pièce retourne au magasin.</p>
            @endif
        </section>

        {{-- ④ Rapport et signature --}}
        <section x-show="step === 4" @if ($step !== 4) x-cloak @endif class="flex flex-col gap-3">
            @if ($repaired)
                {{-- Signé : plus rien à faire ici, la réparation attend sa vérification. --}}
                <div class="flex gap-3 px-3.5 py-3 rounded-xl bg-ok-bg text-[13.5px] text-[#155C40]">
                    <x-nav-icon name="shield" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span><strong>En attente de vérification.</strong> Un responsable contrôle la réparation, puis le service demandeur confirme. Vous serez prévenu du résultat.</span>
                </div>
            @endif
            @include('work-orders.partials.intervention-report', ['inWizard' => true])
        </section>
    </div>

    {{-- Retour / Continuer : collés au-dessus de la barre du bas sur téléphone (la fiche la garde). --}}
    <div class="sticky bottom-[calc(64px+env(safe-area-inset-bottom))] tab:bottom-0 desk:static z-20 -mx-4 tab:mx-0 px-4 tab:px-0 pt-3 pb-3 desk:pb-0 bg-white/95 tab:bg-transparent backdrop-blur tab:backdrop-blur-none border-t border-line tab:border-0">
        <div class="flex gap-2.5">
            <button type="button" x-show="step > 1" x-cloak @click="back" class="btn btn-secondary btn-lg !h-12">
                <x-hk.icon name="arrow-left" :size="16" /> Retour
            </button>
            <button type="button" x-show="step < 4" @click="next" :disabled="! stepValid" class="btn btn-primary btn-lg !h-12 flex-1">
                <span x-text="step === 1 && ! started ? 'Je suis sur place' : (step === 2 && running ? 'Arrêter le chrono et continuer' : 'Continuer')">Continuer</span>
                <x-hk.icon name="arrow-right" :size="16" />
            </button>
            @if ($report?->is_signed)
                {{-- Rapport signé : on sort, en attendant la vérification. --}}
                <a href="{{ route(auth()->user()->dashboardRoute()) }}" x-show="step === 4" x-cloak class="btn btn-primary btn-lg !h-12 flex-1">
                    <x-hk.icon name="home" :size="16" /> Retour à ma journée
                </a>
            @else
                <button type="submit" form="report-form" x-show="step === 4" x-cloak :disabled="! workDone" class="btn btn-success btn-lg !h-12 flex-1">
                    <x-hk.icon name="check" :size="16" /> Signer et envoyer
                </button>
            @endif
        </div>
        <p x-show="! stepValid && step < 4" class="m-0 mt-1.5 text-center tab:text-left text-[12px] text-ink-grey" x-text="hint"></p>
        @unless ($report?->is_signed)
            <p x-show="step === 4" x-cloak class="m-0 mt-1.5 text-center tab:text-left text-[12px] text-ink-grey"
               x-text="workDone ? 'Sans signature, le rapport reste un brouillon.' : 'Décrivez le travail effectué, puis faites signer.'"></p>
        @endunless
    </div>
</section>
