{{-- Plan des étages (gouvernante) : chaque chambre colorée selon son état, étage par
     étage. Un toucher ouvre son détail : signalements en cours, blocage, dernière
     inspection, et les actions « Signaler » et « Inspecter ».
     Données : HousekeepingSupervisionController::floorPlan(). --}}
@php
    $states = [
        'alert' => ['label' => 'Panne urgente ou en retard', 'tile' => 'bg-danger-bg border-red/40 text-red', 'dot' => 'bg-red'],
        'issue' => ['label' => 'Panne en cours', 'tile' => 'bg-warn-bg border-amber/40 text-warn-ink', 'dot' => 'bg-amber'],
        'blocked' => ['label' => 'Bloquée à la vente', 'tile' => 'bg-navy border-navy text-white', 'dot' => 'bg-navy'],
        'out' => ['label' => 'Hors service', 'tile' => 'bg-[#E4DFD4] border-[#D6D0C4] text-[#8A8578]', 'dot' => 'bg-[#B9B3A6]'],
        'ok' => ['label' => 'Rien à signaler', 'tile' => 'bg-white border-line text-navy', 'dot' => 'bg-white border border-line'],
    ];
    $filters = [
        'all' => ['Toutes', $total],
        'issues' => ['Avec panne', ($counts['alert'] ?? 0) + ($counts['issue'] ?? 0)],
        'blocked' => ['Bloquées / HS', ($counts['blocked'] ?? 0) + ($counts['out'] ?? 0)],
        'due' => ['À inspecter', $counts['due'] ?? 0],
    ];
@endphp

<x-app-layout crumb="Outils de la gouvernante" page-title="Plan des étages">
    <x-slot:primaryAction>
        <a href="{{ route('inspections.index') }}" class="btn btn-secondary"><x-nav-icon name="shield" /> Inspections</a>
    </x-slot:primaryAction>

    <div x-data="{ filter: 'all', room: null,
                   shows(t) { return this.filter === 'all' || (this.filter === 'issues' && ['alert', 'issue'].includes(t.state))
                       || (this.filter === 'blocked' && ['blocked', 'out'].includes(t.state)) || (this.filter === 'due' && t.inspectionDue); } }"
         @keydown.escape.window="room = null"
         class="grid gap-5 split:grid-cols-[minmax(0,1fr)_320px] items-start">

        <div class="flex flex-col gap-5 min-w-0">
            {{-- Filtres et légende --}}
            <section class="bg-white border border-line rounded-xl px-4 pt-3 pb-3.5 flex flex-col gap-3">
                <div class="flex flex-wrap gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line self-start max-w-full" role="group" aria-label="Filtrer les chambres">
                    @foreach ($filters as $key => [$label, $count])
                        <button type="button" @click="filter = '{{ $key }}'" :aria-pressed="filter === '{{ $key }}'"
                                class="px-3 h-[32px] inline-flex items-center gap-1.5 rounded-[7px] text-[12.5px] whitespace-nowrap"
                                :class="filter === '{{ $key }}' ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey font-medium'">
                            {{ $label }} <span class="font-mono text-[11.5px] text-ink-grey">{{ $count }}</span>
                        </button>
                    @endforeach
                </div>
                <ul class="m-0 p-0 list-none flex flex-wrap gap-x-4 gap-y-1.5 text-[12px] text-ink-muted">
                    @foreach ($states as $s)
                        <li class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-[4px] {{ $s['dot'] }}"></span>{{ $s['label'] }}</li>
                    @endforeach
                    <li class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gold"></span>À inspecter (plus de {{ \App\Support\RoomInspectionChecklist::DUE_AFTER_DAYS }} jours)</li>
                </ul>
            </section>

            {{-- Étages --}}
            @forelse ($floors as $floor => $tiles)
                <section class="bg-white border border-line rounded-xl overflow-hidden" x-show="@js($tiles->map(fn ($t) => ['state' => $t['state'], 'inspectionDue' => $t['inspectionDue']])->all()).some((t) => shows(t))">
                    <header class="flex items-baseline justify-between gap-3 px-5 py-3 border-b border-line-soft">
                        <h2 class="m-0 text-[15px] font-semibold text-navy">{{ $floor }}</h2>
                        <span class="text-[12px] text-ink-grey">
                            {{ $tiles->count() }} chambre(s)
                            @if ($n = $tiles->whereIn('state', ['alert', 'issue'])->count()) · <span class="text-warn-ink font-semibold">{{ $n }} avec panne</span>@endif
                        </span>
                    </header>
                    <div class="grid grid-cols-4 min-[420px]:grid-cols-5 tab:grid-cols-7 split:grid-cols-6 desk:grid-cols-8 gap-2 p-3">
                        @foreach ($tiles as $tile)
                            <button type="button" x-show="shows(@js($tile))" @click="room = @js($tile)"
                                    class="relative h-14 rounded-[10px] border font-mono text-[15px] font-semibold flex items-center justify-center transition hover:-translate-y-px hover:shadow-sm {{ $states[$tile['state']]['tile'] }}"
                                    :class="room && room.id === {{ $tile['id'] }} && 'ring-2 ring-gold ring-offset-1'"
                                    aria-label="Chambre {{ $tile['number'] }} : {{ $states[$tile['state']]['label'] }}{{ $tile['inspectionDue'] ? ', à inspecter' : '' }}">
                                <span class="{{ $tile['state'] === 'out' ? 'line-through' : '' }}">{{ $tile['number'] }}</span>
                                @if ($tile['inspectionDue'])<span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-gold"></span>@endif
                                @if ($tile['orders']->count() + $tile['others'] > 1)
                                    <span class="absolute bottom-1 right-1.5 text-[9.5px] font-sans font-bold opacity-80">{{ $tile['orders']->count() + $tile['others'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="bg-white border border-line rounded-xl px-5 py-12 text-center text-[13px] text-ink-grey">Aucune chambre n'est enregistrée dans « Lieux ».</div>
            @endforelse
        </div>

        {{-- Détail de la chambre : panneau à droite (≥ 1000 px), feuille qui monte du bas sinon --}}
        <div x-show="room" x-cloak class="split:hidden fixed inset-0 z-40 bg-navy-dark/40" @click="room = null" aria-hidden="true"></div>
        <aside class="fixed split:sticky inset-x-0 bottom-0 split:bottom-auto split:top-[88px] z-50 split:z-auto max-h-[80dvh] overflow-y-auto
                      bg-white border border-line rounded-t-2xl split:rounded-xl shadow-[0_-12px_32px_-12px_rgba(14,33,54,.35)] split:shadow-none"
               :class="room ? '' : 'hidden split:block'" aria-live="polite">
            <template x-if="! room">
                <div class="px-5 py-10 flex flex-col items-center gap-2 text-center">
                    <span class="w-[42px] h-[42px] rounded-full bg-line-soft text-ink-grey flex items-center justify-center"><x-hk.icon name="building" :size="18" /></span>
                    <span class="text-[14px] font-semibold text-navy">Touchez une chambre</span>
                    <span class="text-[12.5px] text-ink-grey">Son état, ses pannes et sa dernière inspection s'affichent ici.</span>
                </div>
            </template>
            <template x-if="room">
                <div class="flex flex-col">
                    <header class="flex items-center gap-3 px-5 py-4 border-b border-line-soft">
                        <div class="flex-1 min-w-0">
                            <h2 class="m-0 text-[17px] font-semibold text-navy">Chambre <span class="font-mono" x-text="room.number"></span></h2>
                            <p class="m-0 text-[12.5px] text-ink-muted" x-text="room.floor + ' · ' + room.statusLabel + (room.block ? ' · ' + room.block : '')"></p>
                        </div>
                        <button type="button" @click="room = null" class="w-9 h-9 rounded-lg flex items-center justify-center text-ink-grey hover:bg-paper" aria-label="Fermer"><x-hk.icon name="x" :size="16" /></button>
                    </header>

                    <div class="px-5 py-4 flex flex-col gap-3">
                        <h3 class="m-0 text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Signalements en cours</h3>
                        <template x-for="o in room.orders" :key="o.code">
                            <a :href="o.url" class="flex items-center gap-2 px-3 py-2.5 rounded-[10px] border border-line hover:bg-paper">
                                <span class="flex-1 min-w-0">
                                    <span class="block text-[13.5px] font-semibold text-navy truncate" x-text="o.label"></span>
                                    <span class="block text-[12px] text-ink-muted"><span class="font-mono" x-text="o.code"></span> · <span x-text="o.status"></span></span>
                                </span>
                                <span x-show="o.urgent" class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-hk-pending-bg text-hk-pending">Urgent</span>
                                <span x-show="o.late" class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red text-white">En retard</span>
                            </a>
                        </template>
                        <p x-show="! room.orders.length && ! room.others" class="m-0 text-[13px] text-ink-grey">Aucun signalement en cours.</p>
                        <p x-show="room.others" class="m-0 text-[12.5px] text-ink-muted" x-text="room.others + ' OT en cours ouvert(s) par un autre service (maintenance, réception).'"></p>

                        <h3 class="m-0 mt-1 text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">Inspection</h3>
                        <p class="m-0 text-[13px]" :class="room.inspectionDue ? 'text-warn-ink font-medium' : 'text-ink-body'"
                           x-text="room.lastInspection ? 'Dernière inspection ' + room.lastInspection + (room.inspectionDue ? ' : à refaire' : '') : 'Jamais inspectée'"></p>
                    </div>

                    <div class="px-5 py-4 border-t border-line-soft bg-paper/60 grid grid-cols-2 gap-2.5" x-show="room.state !== 'out'">
                        <a :href="@js(route('quick-reports.create')) + '?chambre=' + encodeURIComponent(room.number)" class="btn btn-secondary"><x-nav-icon name="mic" /> Signaler</a>
                        <form method="POST" action="{{ route('inspections.store') }}">
                            @csrf
                            <input type="hidden" name="room_number" :value="room.number">
                            <button type="submit" class="btn btn-primary w-full"><x-nav-icon name="shield" /> Inspecter</button>
                        </form>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</x-app-layout>
