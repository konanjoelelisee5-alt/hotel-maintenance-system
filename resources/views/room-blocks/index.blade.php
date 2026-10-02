<x-app-layout :crumb="auth()->user()->role->dispatchesWork() ? 'Exploitation' : 'Mon espace'" page-title="Chambres bloquées">
    @php
        $user = auth()->user();
        $canRequest = $user->can('requestAny', \App\Models\RoomBlock::class);
        $counters = array_filter([
            ['#a-valider', 'Demandes à valider', $pending->count(), 'en attente de la réception', 'amber'],
            ['#hors-vente', 'Chambres hors vente', $blocked->count(), 'retirées de la vente', 'red'],
            $canRequest ? ['#a-decider', 'Libres en panne', $candidates->count(), 'faut-il les bloquer ?', 'navy'] : null,
            ['#clients', 'Clients concernés', $guests->count(), 'chambres occupées en panne', 'gold'],
        ]);
        $dot = ['amber' => 'bg-amber', 'red' => 'bg-red', 'navy' => 'bg-navy', 'gold' => 'bg-gold'];
    @endphp

    <div class="ui-form flex flex-col gap-5">

        {{-- Compteurs : chacun mène à sa section. --}}
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <div class="grid grid-cols-2 {{ count($counters) === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-px bg-line-soft">
                @foreach ($counters as [$anchor, $label, $count, $sub, $tone])
                    <a href="{{ $anchor }}" class="bg-white px-5 py-4 flex flex-col gap-1 hover:bg-paper transition">
                        <span class="flex items-center gap-2 text-[12.5px] text-[#4A4639]">
                            <span class="w-[7px] h-[7px] rounded-full {{ $dot[$tone] }}"></span> {{ $label }}
                        </span>
                        <span class="text-[28px] leading-tight font-semibold tracking-tight {{ $count > 0 && in_array($tone, ['amber', 'red'], true) ? \App\Support\Swatch::text($tone) : 'text-navy' }}">{{ $count }}</span>
                        <span class="text-[12px] text-ink-grey">{{ $sub }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Le circuit, et le rappel Opera (l'application n'y est pas reliée). --}}
            <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-6 px-5 py-3.5 border-t border-line-soft bg-paper/50">
                <ol class="m-0 p-0 list-none flex items-center gap-2 text-[12px] text-[#4A4639] flex-wrap">
                    @foreach ([['Demande', 'gouvernante / maintenance'], ['Décision', 'réception'], ['Remise en vente', 'gouvernante, après vérification']] as [$step, $who])
                        <li class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-navy text-white text-[10.5px] font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                            <span><strong class="text-navy">{{ $step }}</strong> · {{ $who }}</span>
                            @unless ($loop->last)<x-nav-icon name="back" class="w-3.5 h-3.5 rotate-180 text-ink-grey" />@endunless
                        </li>
                    @endforeach
                </ol>
                <p class="m-0 lg:ml-auto flex items-start gap-2 text-[12px] text-[#7A5A16]">
                    <x-nav-icon name="alert" class="w-4 h-4 flex-shrink-0 mt-px" />
                    <span><strong>Opera n'est pas mis à jour automatiquement</strong> : refaites chaque blocage / remise en vente dans Opera.</span>
                </p>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)] gap-5 items-start">

            {{-- ===== Colonne principale : ce qui demande une action ===== --}}
            <div class="flex flex-col gap-5 min-w-0">

                {{-- 1. Demandes à valider (la réception décide : c'est elle qui vend les chambres) --}}
                <x-panel id="a-valider" title="Demandes de blocage à valider" icon="ban" flush>
                    <x-slot:badge>
                        <span class="px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ $pending->isNotEmpty() ? 'bg-[#FBF1DF] text-[#7A5A16]' : 'bg-line-soft text-[#4A4639]' }}">{{ $pending->count() }}</span>
                    </x-slot:badge>

                    <div class="divide-y divide-line-soft">
                        @forelse ($pending as $block)
                            <x-room-row :room="$block->room" tone="amber" :title="$block->reason" :work-order="$block->workOrder">
                                <x-slot:meta>
                                    <span class="inline-flex items-center gap-1"><x-nav-icon name="user" class="w-3.5 h-3.5" /> Demandé par {{ $block->requester->name }}</span>
                                    <span class="inline-flex items-center gap-1"><x-nav-icon name="clock" class="w-3.5 h-3.5" /> {{ $block->created_at->locale('fr')->diffForHumans() }}</span>
                                    @if ($block->workOrder?->status === 'annule')
                                        <span class="inline-flex items-center gap-1 text-red font-semibold"><x-nav-icon name="x" class="w-3.5 h-3.5" /> OT annulé : demande sans objet</span>
                                    @endif
                                </x-slot:meta>
                                <x-slot:actions>
                                    @can('decide', $block)
                                        <div class="flex flex-wrap gap-2" x-data="{ refusing: false }">
                                            <form method="POST" action="{{ route('room-blocks.approve', $block) }}" x-show="!refusing">
                                                @csrf
                                                <button type="submit" class="btn btn-primary"><x-nav-icon name="check" /> Accepter le blocage</button>
                                            </form>
                                            <button type="button" x-show="!refusing" @click="refusing = true" class="btn btn-secondary">Refuser</button>
                                            <form method="POST" action="{{ route('room-blocks.refuse', $block) }}" x-show="refusing" x-cloak class="flex flex-wrap gap-2">
                                                @csrf
                                                <input type="text" name="decision_note" maxlength="500" aria-label="Motif du refus" placeholder="Pourquoi ? (ex. client VIP ce soir)" class="!w-auto min-w-[220px]">
                                                <button type="submit" class="btn btn-danger-solid">Confirmer le refus</button>
                                                <button type="button" @click="refusing = false" class="btn btn-ghost">Annuler</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full bg-[#FBF1DF] text-[#7A5A16] text-[12px] font-semibold">En attente de la réception</span>
                                    @endcan
                                </x-slot:actions>
                            </x-room-row>
                        @empty
                            @include('room-blocks.partials.empty', ['icon' => 'check', 'text' => 'Aucune demande en attente.'])
                        @endforelse
                    </div>
                </x-panel>

                {{-- 2. Chambres hors vente (la gouvernante les remet en vente après vérification) --}}
                <x-panel id="hors-vente" title="Chambres hors vente" icon="building" flush>
                    <x-slot:badge>
                        <span class="px-2 py-0.5 rounded-full text-[11.5px] font-semibold {{ $blocked->isNotEmpty() ? 'bg-[#FBE4E1] text-red' : 'bg-line-soft text-[#4A4639]' }}">{{ $blocked->count() }}</span>
                    </x-slot:badge>

                    <div class="divide-y divide-line-soft">
                        @forelse ($blocked as $block)
                            @php
                                $repaired = $block->workOrder && in_array($block->workOrder->status, ['resolu', 'ferme'], true);
                                // OT annulé (doublon, erreur) : plus rien ne justifie le blocage, à vérifier puis remettre en vente.
                                $cancelled = $block->workOrder?->status === 'annule';
                            @endphp
                            <x-room-row :room="$block->room" tone="red" :title="$block->reason" :work-order="$block->workOrder">
                                <x-slot:badge>
                                    @if ($repaired)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#E6F3EC] text-green text-[11.5px] font-semibold"><x-nav-icon name="check" class="w-3 h-3" /> Réparée</span>
                                    @elseif ($cancelled)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#FBE4E1] text-red text-[11.5px] font-semibold"><x-nav-icon name="x" class="w-3 h-3" /> OT annulé</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[#FBF1DF] text-[#7A5A16] text-[11.5px] font-semibold"><x-nav-icon name="wrench" class="w-3 h-3" /> Réparation en cours</span>
                                    @endif
                                </x-slot:badge>
                                <x-slot:meta>
                                    @if ($block->decided_at)
                                        <span class="inline-flex items-center gap-1"><x-nav-icon name="clock" class="w-3.5 h-3.5" /> Hors vente depuis {{ \App\Support\Duration::human($block->decided_at->diffInMinutes(now())) }}</span>
                                    @endif
                                    <span class="inline-flex items-center gap-1"><x-nav-icon name="user" class="w-3.5 h-3.5" /> Bloquée par {{ $block->decider?->name ?? '—' }}</span>
                                </x-slot:meta>
                                @can('release', $block)
                                    <x-slot:actions>
                                        <form method="POST" action="{{ route('room-blocks.release', $block) }}"
                                              data-confirm="{{ $repaired || $cancelled ? 'Vous avez vérifié la chambre : elle redevient disponible à la vente.' : 'La réparation n’est pas terminée : la chambre sera quand même remise en vente.' }}"
                                              data-confirm-title="Remettre la chambre en vente ?" data-confirm-label="Remettre en vente">
                                            @csrf
                                            <button type="submit" class="btn {{ $repaired || $cancelled ? 'btn-primary' : 'btn-secondary' }}"><x-nav-icon name="check" /> Vérifiée : remettre en vente</button>
                                        </form>
                                    </x-slot:actions>
                                @endcan
                            </x-room-row>
                        @empty
                            @include('room-blocks.partials.empty', ['icon' => 'check', 'text' => 'Aucune chambre bloquée : tout est en vente.'])
                        @endforelse
                    </div>
                </x-panel>
            </div>

            {{-- ===== Colonne latérale : à surveiller ===== --}}
            <aside class="flex flex-col gap-5 min-w-0">

                {{-- 3. Chambres libres en panne sans blocage (pour qui peut demander) --}}
                @if ($canRequest)
                    <x-panel id="a-decider" title="Libres en panne : faut-il bloquer ?" icon="info" flush>
                        <x-slot:badge>
                            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-[#4A4639]">{{ $candidates->count() }}</span>
                        </x-slot:badge>
                        <div class="divide-y divide-line-soft">
                            @forelse ($candidates as $wo)
                                <x-room-row :room="$wo->room" tone="navy" :title="$wo->title" :work-order="$wo" stacked>
                                    <x-slot:meta>
                                        <span>{{ $wo->room_occupancy?->label() }}</span>
                                        <span>signalé par {{ $wo->reporter?->name }} · {{ $wo->created_at->locale('fr')->diffForHumans() }}</span>
                                    </x-slot:meta>
                                    <x-slot:actions>
                                        <form method="POST" action="{{ route('room-blocks.store', $wo) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary"><x-nav-icon name="ban" /> Demander le blocage</button>
                                        </form>
                                    </x-slot:actions>
                                </x-room-row>
                            @empty
                                @include('room-blocks.partials.empty', ['icon' => 'check', 'text' => 'Rien à décider.'])
                            @endforelse
                        </div>
                    </x-panel>
                @endif

                {{-- 4. Clients concernés : pas de blocage, la réception gère (excuses, délogement dans Opera) --}}
                <x-panel id="clients" title="Chambres occupées en panne" icon="user" flush>
                    <x-slot:badge>
                        <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-[#4A4639]">{{ $guests->count() }}</span>
                    </x-slot:badge>
                    <p class="m-0 px-5 pt-3 text-[12px] text-ink-grey">On répare avant le retour du client ; sinon la réception décide d'un délogement.</p>
                    <div class="divide-y divide-line-soft">
                        @forelse ($guests as $wo)
                            @php $late = $wo->due_date && $wo->due_date->isPast(); @endphp
                            <x-room-row :room="$wo->room" tone="gold" :title="$wo->title" :work-order="$wo">
                                <x-slot:meta>
                                    <span>{{ $wo->room_occupancy?->label() }}</span>
                                    @if ($wo->due_date)
                                        <span class="inline-flex items-center gap-1 {{ $late ? 'text-red font-semibold' : '' }}">
                                            <x-nav-icon name="clock" class="w-3.5 h-3.5" />
                                            à réparer avant {{ $wo->due_date->format('H\hi') }}{{ $wo->due_date->isToday() ? '' : ' le '.$wo->due_date->format('d/m') }}
                                        </span>
                                    @endif
                                    <span class="{{ $wo->assignee ? '' : 'text-amber font-medium' }}">{{ $wo->assignee ? $wo->assignee->name.' · '.$wo->status_label : 'pas encore de technicien' }}</span>
                                </x-slot:meta>
                            </x-room-row>
                        @empty
                            @include('room-blocks.partials.empty', ['icon' => 'check', 'text' => 'Aucune chambre occupée en panne.'])
                        @endforelse
                    </div>
                </x-panel>

                {{-- 5. Historique --}}
                @if ($history->isNotEmpty())
                    <x-panel title="Historique récent" icon="history" collapsible :open="false">
                        <x-slot:badge>
                            <span class="px-2 py-0.5 rounded-full bg-line-soft text-[11.5px] font-semibold text-[#4A4639]">{{ $history->count() }}</span>
                        </x-slot:badge>
                        <ul class="m-0 p-0 list-none flex flex-col gap-3">
                            @foreach ($history as $block)
                                @php $released = $block->status === \App\Models\RoomBlock::RELEASED; @endphp
                                <li class="flex gap-3 text-[12.5px]">
                                    <span class="mt-1 w-2 h-2 flex-shrink-0 rounded-full {{ $released ? 'bg-green' : 'bg-ink-grey' }}"></span>
                                    <span class="min-w-0">
                                        <span class="font-semibold text-navy">{{ $block->room->label }}</span>
                                        <span class="text-[#4A4639]">· {{ $block->status_label }}</span>
                                        <span class="block text-ink-grey">
                                            @if ($released)
                                                par {{ $block->releaser?->name }} {{ $block->released_at?->locale('fr')->diffForHumans() }}
                                            @else
                                                par {{ $block->decider?->name }}{{ $block->decision_note ? ' : « '.$block->decision_note.' »' : '' }}
                                            @endif
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </x-panel>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
