<x-app-layout crumb="Chambres · vente" page-title="Chambres bloquées">
    @php $user = auth()->user(); @endphp

    {{-- L'application n'est pas reliée à Opera : rappel permanent de la double saisie. --}}
    <div class="px-4 py-3 rounded-lg bg-[#FBF1DF] text-[#7A5A16] text-[13px] leading-relaxed">
        <strong>Opera n'est pas mis à jour automatiquement.</strong> Après chaque blocage ou remise en vente validé ici,
        la réception fait la même chose dans Opera (chambre hors service / remise en service).
    </div>

    {{-- 1. Demandes à valider --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-[18px] py-[15px] border-b border-line-soft">
            <h2 class="text-[14.5px] font-semibold">🚫 Demandes de blocage à valider</h2>
            <p class="text-[11.5px] text-ink-grey">La réception accepte ou refuse : c'est elle qui vend les chambres.</p>
        </div>
        @forelse ($pending as $block)
            <div class="px-[18px] py-3 border-b border-line-soft last:border-0 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="text-[14px] font-semibold">{{ $block->room->label }} <span class="font-normal text-ink-grey">— {{ $block->reason }}</span></div>
                    <div class="text-[12px] text-ink-grey">Demandé par {{ $block->requester->name }} · {{ $block->created_at->locale('fr')->diffForHumans() }}
                        @if ($block->workOrder) · {{ $block->workOrder->code() }} @endif
                        @if ($block->workOrder?->status === 'annule') · <span class="text-red font-semibold">❌ OT annulé : demande sans objet</span> @endif</div>
                </div>
                @can('decide', $block)
                    <div class="flex flex-wrap gap-2" x-data="{ refusing: false }">
                        <form method="POST" action="{{ route('room-blocks.approve', $block) }}" x-show="!refusing">
                            @csrf
                            <button type="submit" class="h-10 px-4 rounded-lg bg-green text-white text-[13px] font-semibold">✓ Accepter le blocage</button>
                        </form>
                        <button type="button" x-show="!refusing" @click="refusing = true" class="h-10 px-4 rounded-lg border border-line text-[13px] font-semibold">Refuser</button>
                        <form method="POST" action="{{ route('room-blocks.refuse', $block) }}" x-show="refusing" class="flex flex-wrap gap-2">
                            @csrf
                            <input type="text" name="decision_note" maxlength="500" placeholder="Pourquoi ? (ex. client VIP ce soir)" class="h-10 rounded-lg border-line text-[13px] min-w-[220px]">
                            <button type="submit" class="h-10 px-4 rounded-lg bg-red text-white text-[13px] font-semibold">Confirmer le refus</button>
                        </form>
                    </div>
                @else
                    <span class="text-[12px] font-semibold text-amber">En attente de la réception</span>
                @endcan
            </div>
        @empty
            <p class="px-[18px] py-5 text-[13px] text-ink-grey">Aucune demande en attente.</p>
        @endforelse
    </section>

    {{-- 2. Chambres actuellement hors vente --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-[18px] py-[15px] border-b border-line-soft">
            <h2 class="text-[14.5px] font-semibold">🔴 Chambres hors vente</h2>
            <p class="text-[11.5px] text-ink-grey">La gouvernante les remet en vente après avoir vérifié la réparation.</p>
        </div>
        @forelse ($blocked as $block)
            @php
                $repaired = $block->workOrder && in_array($block->workOrder->status, ['resolu', 'ferme'], true);
                // OT annulé (doublon, erreur) : plus rien ne justifie le blocage, à vérifier puis remettre en vente.
                $cancelled = $block->workOrder?->status === 'annule';
            @endphp
            <div class="px-[18px] py-3 border-b border-line-soft last:border-0 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="text-[14px] font-semibold">{{ $block->room->label }} <span class="font-normal text-ink-grey">— {{ $block->reason }}</span></div>
                    <div class="text-[12px] text-ink-grey">Bloquée {{ $block->decided_at?->locale('fr')->diffForHumans() }} par {{ $block->decider?->name }}
                        · <span class="{{ $repaired ? 'text-green font-semibold' : ($cancelled ? 'text-red font-semibold' : '') }}">{{ $repaired ? '✅ réparée' : ($cancelled ? '❌ OT annulé' : '🔧 réparation en cours') }}</span></div>
                </div>
                @can('release', $block)
                    <form method="POST" action="{{ route('room-blocks.release', $block) }}"
                          onsubmit="return confirm(@js($repaired || $cancelled ? 'Vous avez vérifié la chambre : la remettre en vente ?' : 'La réparation n\'est pas terminée. Remettre quand même la chambre en vente ?'));">
                        @csrf
                        <button type="submit" class="h-10 px-4 rounded-lg text-[13px] font-semibold {{ $repaired || $cancelled ? 'bg-green text-white' : 'border border-line' }}">🟢 Vérifiée : remettre en vente</button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="px-[18px] py-5 text-[13px] text-ink-grey">Aucune chambre bloquée.</p>
        @endforelse
    </section>

    {{-- 3. Chambres libres en panne sans blocage (pour qui peut demander) --}}
    @can('requestAny', \App\Models\RoomBlock::class)
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <div class="px-[18px] py-[15px] border-b border-line-soft">
                <h2 class="text-[14.5px] font-semibold">❓ Chambres libres en panne : faut-il les bloquer ?</h2>
                <p class="text-[11.5px] text-ink-grey">Signalées libres ou en départ, pas encore bloquées.</p>
            </div>
            @forelse ($candidates as $wo)
                <div class="px-[18px] py-3 border-b border-line-soft last:border-0 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-[14px] font-semibold">{{ $wo->room->label }} <span class="font-normal text-ink-grey">— {{ $wo->title }}</span></div>
                        <div class="text-[12px] text-ink-grey">{{ $wo->room_occupancy?->emoji() }} {{ $wo->room_occupancy?->label() }} · signalé par {{ $wo->reporter?->name }} · {{ $wo->created_at->locale('fr')->diffForHumans() }}</div>
                    </div>
                    <form method="POST" action="{{ route('room-blocks.store', $wo) }}">
                        @csrf
                        <button type="submit" class="h-10 px-4 rounded-lg bg-navy text-white text-[13px] font-semibold">🚫 Demander le blocage</button>
                    </form>
                </div>
            @empty
                <p class="px-[18px] py-5 text-[13px] text-ink-grey">Rien à décider.</p>
            @endforelse
        </section>
    @endcan

    {{-- 4. Clients concernés : la réception s'en occupe (excuses, délogement dans Opera) --}}
    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="px-[18px] py-[15px] border-b border-line-soft">
            <h2 class="text-[14.5px] font-semibold">🧳 Chambres occupées en panne</h2>
            <p class="text-[11.5px] text-ink-grey">Pas de blocage : on répare avant le retour du client, sinon la réception décide d'un délogement.</p>
        </div>
        @forelse ($guests as $wo)
            @php $late = $wo->due_date && $wo->due_date->isPast(); @endphp
            <div class="px-[18px] py-3 border-b border-line-soft last:border-0">
                <div class="text-[14px] font-semibold">{{ $wo->room?->label }} <span class="font-normal text-ink-grey">— {{ $wo->title }}</span></div>
                <div class="text-[12px] text-ink-grey">
                    {{ $wo->room_occupancy?->emoji() }} {{ $wo->room_occupancy?->label() }}
                    @if ($wo->due_date) · <span class="{{ $late ? 'text-red font-semibold' : '' }}">à réparer avant {{ $wo->due_date->format('H\hi') }}{{ $wo->due_date->isToday() ? '' : ' le '.$wo->due_date->format('d/m') }}</span> @endif
                    · {{ $wo->assignee ? $wo->assignee->name.' · '.$wo->status_label : 'pas encore de technicien' }}
                </div>
            </div>
        @empty
            <p class="px-[18px] py-5 text-[13px] text-ink-grey">Aucune chambre occupée en panne.</p>
        @endforelse
    </section>

    {{-- 5. Historique --}}
    @if ($history->isNotEmpty())
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <div class="px-[18px] py-[15px] border-b border-line-soft">
                <h2 class="text-[14.5px] font-semibold">Historique récent</h2>
            </div>
            @foreach ($history as $block)
                <div class="px-[18px] py-2.5 border-b border-line-soft last:border-0 text-[12.5px]">
                    <span class="font-semibold">{{ $block->room->label }}</span> — {{ $block->status_label }}
                    <span class="text-ink-grey">
                        @if ($block->status === \App\Models\RoomBlock::RELEASED)
                            par {{ $block->releaser?->name }} {{ $block->released_at?->locale('fr')->diffForHumans() }}
                        @else
                            par {{ $block->decider?->name }}{{ $block->decision_note ? ' : « '.$block->decision_note.' »' : '' }}
                        @endif
                    </span>
                </div>
            @endforeach
        </section>
    @endif
</x-app-layout>
