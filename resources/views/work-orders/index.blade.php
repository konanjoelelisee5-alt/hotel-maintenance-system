@php
    $title = match (true) {
        auth()->user()->role === \App\Enums\UserRole::Technicien => 'Mes ordres de travail',
        auth()->user()->role === \App\Enums\UserRole::Housekeeping => 'Historique de mes signalements',
        auth()->user()->role === \App\Enums\UserRole::Reception => 'Demandes de la réception',
        default => 'Ordres de travail',
    };

    // Les liens de la page conservent recherche, filtres et tri les uns pour les autres.
    $state = array_filter(['filter' => $filter, 'q' => $q, 'status' => request('status'), 'priority_id' => request('priority_id'), 'sort' => $sort], 'filled');
    $here = fn (array $params) => route('work-orders.index', array_filter(array_merge($state, $params), 'filled'));
    $refined = filled($q) || request()->filled('status') || request()->filled('priority_id');

    $listTitle = match ($filter) {
        'mine' => collect($filters)->firstWhere('key', 'mine')['label'] ?? 'Mes ordres',
        'open' => 'Ordres ouverts',
        'urgent' => 'Ordres urgents',
        'unassigned' => 'Ordres non affectés',
        'late' => 'Ordres en retard SLA',
        default => 'Tous les ordres',
    };
@endphp

<x-app-layout crumb="Espace de travail" :page-title="$title">
    @can('create', \App\Models\WorkOrder::class)
        <x-slot:primaryAction>
            <a href="{{ route('work-orders.create') }}" data-modal class="flex-shrink-0 inline-flex items-center h-[38px] px-4 rounded-[9px] bg-navy text-white text-[13px] font-semibold whitespace-nowrap hover:bg-navy-light">+ Nouvel ordre</a>
        </x-slot:primaryAction>
    @endcan

    {{-- Indicateurs : même bande que la Supervision ; chacun ouvre la liste filtrée. --}}
    <section class="bg-white border border-line rounded-xl overflow-x-auto">
        <div class="flex divide-x divide-line min-w-max lg:min-w-0">
            @foreach ($stats as $s)
                <a href="{{ $here(['filter' => $s['filter'], 'page' => null]) }}"
                   @if ($filter === $s['filter']) aria-current="true" @endif
                   class="flex-1 min-w-[170px] flex flex-col gap-1 px-6 py-5 transition {{ $filter === $s['filter'] ? 'bg-paper shadow-[inset_0_-2px_0_theme(colors.navy)]' : 'hover:bg-paper' }}">
                    <span class="flex items-center gap-2 text-[13px] text-[#4A4639] whitespace-nowrap">
                        <span class="w-[7px] h-[7px] rounded-full {{ $s['dot'] }} flex-shrink-0"></span>
                        {{ $s['label'] }}
                    </span>
                    <span class="text-[30px] leading-tight font-semibold tracking-tight text-navy whitespace-nowrap">{{ $s['value'] }}</span>
                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap">{{ $s['sub'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
        {{-- Titre + onglets soulignés avec compteur --}}
        <div class="flex items-end gap-4 flex-wrap px-4 sm:px-6 pt-5 border-b border-line">
            <div class="flex flex-col gap-0.5 pb-3.5">
                <h2 class="text-[17px] font-semibold text-navy">{{ $listTitle }}</h2>
                <div class="text-[12.5px] text-ink-grey">{{ $workOrders->total() }} ordre(s) · triés par {{ ['due' => 'échéance SLA', 'priority' => 'priorité', 'created' => 'date de création'][$sort] }}</div>
            </div>

            <nav class="flex gap-1 ml-auto overflow-x-auto -mb-px max-w-full" aria-label="Filtres">
                @foreach ($filters as $f)
                    @php $active = $filter === $f['key']; @endphp
                    <a href="{{ $here(['filter' => $f['key'], 'page' => null]) }}"
                       @if ($active) aria-current="page" @endif
                       class="flex items-center gap-1.5 px-3.5 pb-3 pt-1 border-b-2 text-[13.5px] whitespace-nowrap {{ $active ? 'border-navy text-navy font-semibold' : 'border-transparent text-[#6C6658] font-medium hover:text-navy' }}">
                        {{ $f['label'] }}
                        <span class="font-mono text-[11.5px] text-ink-grey">{{ $filterCounts[$f['key']] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Barre d'outils : recherche, affinage, tri (envoi automatique au changement) --}}
        <form action="{{ route('work-orders.index') }}" method="GET" class="flex items-center gap-2.5 flex-wrap px-4 sm:px-6 py-3.5 bg-paper/60 border-b border-line-soft">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <label class="flex items-center gap-2 h-[38px] w-full sm:w-[280px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="q" value="{{ $q }}" placeholder="Titre, n° de chambre…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13px] placeholder:text-ink-grey focus:ring-0">
            </label>

            @php $select = 'h-[38px] rounded-[9px] border border-line bg-white text-[13px] text-[#26496B] font-medium pl-3 pr-8 py-0 focus:border-navy focus:ring-0'; @endphp
            <label class="sr-only" for="wo-status">Statut</label>
            <select id="wo-status" name="status" onchange="this.form.submit()" class="{{ $select }} flex-1 sm:flex-none">
                <option value="">Tous les statuts</option>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="wo-priority">Priorité</label>
            <select id="wo-priority" name="priority_id" onchange="this.form.submit()" class="{{ $select }} flex-1 sm:flex-none">
                <option value="">Toutes priorités</option>
                @foreach ($priorities as $p)
                    <option value="{{ $p->id }}" @selected((string) request('priority_id') === (string) $p->id)>{{ $p->label }}</option>
                @endforeach
            </select>

            @if ($refined)
                <a href="{{ route('work-orders.index', ['filter' => $filter, 'sort' => $sort]) }}" class="text-[12.5px] font-semibold text-[#6C6658] hover:text-navy whitespace-nowrap">Réinitialiser</a>
            @endif

            <label class="flex items-center gap-1.5 sm:ml-auto text-[12.5px] text-ink-grey whitespace-nowrap">
                <x-nav-icon name="sort" class="w-3.5 h-3.5" />
                <span class="sr-only">Trier par</span>
                <select name="sort" onchange="this.form.submit()" class="{{ $select }}">
                    @foreach ($sortOptions as $key => $label)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        @if ($workOrders->isEmpty())
            <div class="px-5 py-14 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-ink-grey">
                    <x-nav-icon name="clipboard" class="w-5 h-5" />
                </div>
                <div class="text-[14px] font-semibold">Aucun ordre ne correspond</div>
                <div class="text-[12.5px] text-ink-grey max-w-[380px] leading-relaxed">
                    {{ $refined ? 'Élargissez la recherche ou réinitialisez les critères.' : "Rien dans cette vue. Changez d'onglet pour voir les autres ordres." }}
                </div>
            </div>
        @else
            {{-- Mobile / tablette : cartes empilées (un tableau serait illisible en dessous de 1024px) --}}
            <div class="lg:hidden flex flex-col gap-2.5 p-3">
                @foreach ($workOrders as $w)
                    @include('work-orders.partials._ot-card', ['w' => $w, 'showDueDate' => true])
                @endforeach
            </div>

            {{-- Desktop : même tableau que la file de la Supervision, liseré rouge si en retard --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full min-w-[860px] text-left border-collapse">
                    <thead>
                        <tr class="bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">
                            <th class="pl-6 pr-3 py-3 w-[108px]">Réf.</th>
                            <th class="px-3 py-3">Intervention</th>
                            <th class="px-3 py-3 w-[120px]">Statut</th>
                            <th class="px-3 py-3 w-[180px]">Technicien</th>
                            <th class="px-3 py-3 w-[110px]">Créé le</th>
                            <th class="pl-3 pr-6 py-3 w-[190px]">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workOrders as $w)
                            @php $sla = $w->slaSummary(); @endphp
                            <tr class="border-b border-line-soft last:border-b-0 hover:bg-paper/60 {{ $sla['late'] ? 'shadow-[inset_3px_0_0_theme(colors.red)]' : '' }}">
                                <td class="pl-6 pr-3 py-4 align-middle font-mono text-[12.5px] text-[#4A4639] whitespace-nowrap">
                                    <a href="{{ route('work-orders.show', $w) }}" class="hover:text-navy">{{ $w->code() }}</a>
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('work-orders.show', $w) }}" class="text-[14.5px] font-semibold text-navy hover:underline">{{ $w->title }}</a>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11.5px] font-semibold whitespace-nowrap" style="background-color: {{ $w->priority->color }}18; color: {{ $w->priority->color }};">{{ $w->priority->label }}</span>
                                    </div>
                                    <div class="text-[13px] text-[#6C6658] mt-0.5">{{ $w->room?->label ?? '—' }} · {{ $w->equipment?->name ?? $w->type->label }}</div>
                                </td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap">
                                    <x-work-order-status-badge :status="$w->status" />
                                </td>
                                <td class="px-3 py-4 align-middle">
                                    @if ($w->assignee)
                                        <span class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-[11px] font-bold text-navy flex-shrink-0">{{ $w->assignee->initialsOrGenerated() }}</span>
                                            <span class="text-[13.5px] truncate max-w-[130px]" title="{{ $w->assignee->name }}">{{ $w->assignee->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-[13px] text-[#A09A8C]">— à affecter</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 align-middle whitespace-nowrap">
                                    <div class="font-mono text-[12.5px] text-[#4A4639]">{{ $w->created_at->format('d/m/Y') }}</div>
                                    <div class="text-[11.5px] text-ink-grey mt-0.5">{{ $w->reporter?->name ?? '—' }}</div>
                                </td>
                                <td class="pl-3 pr-6 py-4 align-middle">
                                    <div class="font-mono text-[12px] whitespace-nowrap {{ \App\Support\Swatch::text($sla['color']) }}">{{ $sla['text'] }}</div>
                                    @if ($sla['width'] > 0)
                                        <div class="mt-1.5 h-[4px] rounded-full bg-line-soft overflow-hidden">
                                            <div class="h-full {{ \App\Support\Swatch::bg($sla['color']) }}" style="width: {{ $sla['width'] }}%"></div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($workOrders->hasPages())
                <div class="px-4 sm:px-6 py-3.5 border-t border-line bg-paper/60">
                    {{ $workOrders->links() }}
                </div>
            @endif
        @endif
    </section>
</x-app-layout>
