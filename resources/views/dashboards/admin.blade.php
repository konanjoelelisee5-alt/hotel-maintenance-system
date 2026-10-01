<x-app-layout crumb="Opérations / Supervision" page-title="Supervision — Hôtel Président">
    @php
        // Les liens de la page conservent filtre et période l'un pour l'autre.
        $here = fn (array $params) => route('admin.dashboard', array_merge(['filter' => $filter, 'period' => $period], $params));
        $kpiDot = fn (string $label) => match (true) {
            str_contains($label, 'SLA dépassés') => 'bg-red',
            str_contains($label, 'respect') => 'bg-green',
            str_contains($label, 'qualité') => 'bg-gold',
            str_contains($label, 'actifs') => 'bg-blue',
            default => 'bg-navy',
        };
    @endphp

    <x-slot:primaryAction>
        <form method="GET" action="{{ route('work-orders.index') }}" class="flex-shrink-0">
            <label class="flex items-center gap-2 h-[38px] w-[240px] xl:w-[300px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="q" placeholder="Rechercher un ordre, une chambre…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13px] placeholder:text-ink-grey focus:ring-0">
            </label>
        </form>

        <div class="flex-shrink-0 flex items-center gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line" role="group" aria-label="Période">
            @foreach ($periods as $key => [$short])
                <a href="{{ $here(['period' => $key]) }}"
                   @if ($period === $key) aria-current="true" @endif
                   class="px-3 h-[30px] inline-flex items-center rounded-[7px] font-mono text-[12px] whitespace-nowrap {{ $period === $key ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey hover:text-navy' }}">{{ $short }}</a>
            @endforeach
        </div>

        @can('create', \App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" class="flex-shrink-0 inline-flex items-center h-[38px] px-4 rounded-[9px] bg-navy text-white text-[13px] font-semibold whitespace-nowrap hover:bg-navy-light">+ Nouvel ordre</a>
        @endcan
    </x-slot:primaryAction>

    {{-- Indicateurs : une seule bande, séparateurs verticaux ; défile
         horizontalement plutôt que de couper les libellés sur petit écran. --}}
    <section class="bg-white border border-line rounded-xl overflow-x-auto">
        <div class="flex divide-x divide-line min-w-max lg:min-w-0">
            @foreach ($pulse as $p)
                <a href="{{ $here(['filter' => $p['filter']]) }}" class="flex-1 min-w-[180px] flex flex-col gap-1 px-6 py-5 hover:bg-paper transition">
                    <span class="flex items-center gap-2 text-[13px] text-[#4A4639] whitespace-nowrap">
                        <span class="w-[7px] h-[7px] rounded-full {{ $kpiDot($p['label']) }} flex-shrink-0"></span>
                        {{ $p['label'] }}
                    </span>
                    <span class="text-[30px] leading-tight font-semibold tracking-tight text-navy whitespace-nowrap">{{ $p['value'] }}</span>
                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap">{{ $p['sub'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Tableau des ordres + colonne latérale ; la colonne passe dessous
         en dessous de 1100 px. --}}
    <div class="grid gap-5 items-start min-[1100px]:grid-cols-[minmax(0,1fr)_320px] min-[1500px]:grid-cols-[minmax(0,1fr)_380px]">
        <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
            <div class="flex items-end gap-4 flex-wrap px-6 pt-5 border-b border-line">
                <div class="flex flex-col gap-0.5 pb-3.5">
                    <h2 class="text-[17px] font-semibold text-navy">
                        {{ match($filter) { 'urgent' => 'Ordres urgents', 'unassigned' => 'Ordres non affectés', 'late' => 'Ordres en retard SLA', default => 'Tous les ordres' } }}
                    </h2>
                    <div class="text-[12.5px] text-ink-grey">{{ $queue->count() }} ordre(s) affiché(s) · triés par urgence SLA</div>
                </div>

                <nav class="flex gap-1 ml-auto overflow-x-auto -mb-px" aria-label="Filtres">
                    @foreach ($filters as $f)
                        @php $active = $filter === $f['key']; @endphp
                        <a href="{{ $here(['filter' => $f['key']]) }}"
                           @if ($active) aria-current="page" @endif
                           class="flex items-center gap-1.5 px-3.5 pb-3 pt-1 border-b-2 text-[13.5px] whitespace-nowrap {{ $active ? 'border-navy text-navy font-semibold' : 'border-transparent text-[#6C6658] font-medium hover:text-navy' }}">
                            {{ $f['label'] }}
                            <span class="font-mono text-[11.5px] text-ink-grey">{{ $filterCounts[$f['key']] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            @if ($queue->isEmpty())
                <div class="px-5 py-11 flex flex-col items-center gap-2 text-center">
                    <div class="w-[42px] h-[42px] rounded-full bg-[#E6F3EC] flex items-center justify-center text-green text-[17px]">✓</div>
                    <div class="text-[14px] font-semibold">Aucun ordre dans ce filtre</div>
                    <div class="text-[12.5px] text-ink-grey max-w-[340px] leading-relaxed">Rien à traiter dans cette vue. Changez d'onglet ci-dessus pour voir le reste des ordres.</div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left border-collapse">
                        <thead>
                            <tr class="bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">
                                <th class="pl-6 pr-3 py-3 w-[108px]">Réf.</th>
                                <th class="px-3 py-3">Intervention</th>
                                <th class="px-3 py-3 w-[112px]">Statut</th>
                                <th class="px-3 py-3 w-[170px]">Technicien</th>
                                <th class="pl-3 pr-6 py-3 w-[180px]">SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($queue as $w)
                                @php
                                    // Même règle que slaRemainingLabel() : l'échéance passée compte
                                    // aussi, sans attendre que la tâche planifiée pose sla_breached.
                                    $late = $w->sla_breached || $w->sla_resolution_due_at?->isPast();
                                    $slaColor = $late ? 'red' : $w->slaColorClass();
                                @endphp
                                <tr class="border-b border-line-soft last:border-b-0 hover:bg-paper/60 {{ $late ? 'shadow-[inset_3px_0_0_theme(colors.red)]' : '' }}">
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
                                    <td class="pl-3 pr-6 py-4 align-middle">
                                        <div class="font-mono text-[12px] whitespace-nowrap {{ \App\Support\Swatch::text($slaColor) }}">{{ $w->slaRemainingLabel() }}</div>
                                        <div class="mt-1.5 h-[4px] rounded-full bg-line-soft overflow-hidden">
                                            <div class="h-full {{ \App\Support\Swatch::bg($slaColor) }}" style="width: {{ $late ? 100 : $w->slaProgressPercent() }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="px-6 py-3.5 border-t border-line bg-paper/60 text-right">
                <a href="{{ route('work-orders.index') }}" class="text-[13px] font-semibold text-navy hover:underline">Voir tous les ordres →</a>
            </div>
        </section>

        <div class="flex flex-col gap-5 min-w-0">
            <section class="bg-white border border-line rounded-xl px-6 py-5">
                <div class="flex items-baseline justify-between gap-3 mb-4">
                    <h2 class="text-[17px] font-semibold text-navy">{{ $sideA['title'] }}</h2>
                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap">{{ $sideA['sub'] ?? '' }}</span>
                </div>
                <div class="flex flex-col gap-4">
                    @forelse ($sideA['items'] as $item)
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-[7px] h-[7px] rounded-[2px] flex-shrink-0 {{ empty($item['dot']) ? \App\Support\Swatch::bg($item['color']) : '' }}" @if (! empty($item['dot'])) style="background-color: {{ $item['dot'] }}" @endif></span>
                                    <span class="text-[14px] truncate">{{ $item['name'] }}</span>
                                </span>
                                <span class="font-mono text-[12px] text-[#4A4639] whitespace-nowrap">{{ $item['meta'] }}</span>
                            </div>
                            <div class="h-[5px] rounded-full bg-line-soft overflow-hidden">
                                <div class="h-full {{ \App\Support\Swatch::bg($item['color']) }}" style="width: {{ $item['pct'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-[12.5px] text-ink-grey">Rien à signaler.</div>
                    @endforelse
                </div>
            </section>

            <section class="bg-white border border-line rounded-xl px-6 py-5">
                <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-line-soft">
                    <h2 class="text-[17px] font-semibold text-navy">{{ $sideB['title'] }}</h2>
                    <a href="{{ route('activity-logs.index') }}" class="text-[13px] font-semibold text-navy whitespace-nowrap hover:underline">Journal →</a>
                </div>
                <div class="flex flex-col">
                    @forelse ($sideB['items'] as $item)
                        <div class="flex items-start gap-3 py-3 border-b border-line-soft last:border-b-0 last:pb-0">
                            <span class="w-[7px] h-[7px] rounded-full {{ \App\Support\Swatch::bg($item['color']) }} mt-[7px] flex-shrink-0"></span>
                            <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                                <span class="text-[13.5px] leading-snug">{{ $item['label'] }}</span>
                                @if (! empty($item['who']))
                                    <span class="text-[12px] text-ink-grey truncate">{{ $item['who'] }}</span>
                                @endif
                            </span>
                            <span class="font-mono text-[12px] text-ink-grey whitespace-nowrap mt-0.5">{{ $item['time'] ?? '' }}</span>
                        </div>
                    @empty
                        <div class="text-[12.5px] text-ink-grey pt-3">Rien à signaler.</div>
                    @endforelse
                </div>
            </section>

            {{-- Une tâche automatique arrêtée ne se voit nulle part ailleurs :
                 plus d'escalade ni d'OT préventif, sans aucun message d'erreur. --}}
            <section class="bg-white border border-line rounded-xl px-6 py-5">
                <h2 class="text-[17px] font-semibold text-navy mb-3">Tâches automatiques</h2>
                <div class="flex flex-col gap-3">
                    @foreach ($schedulerHealth as $task)
                        @php($color = ['ok' => 'green', 'late' => 'amber', 'never' => 'red'][$task['state']])
                        <div class="flex items-start gap-3">
                            <span class="w-[7px] h-[7px] rounded-full mt-[7px] flex-shrink-0 {{ \App\Support\Swatch::bg($color) }}"></span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-[13.5px] leading-snug">{{ $task['label'] }}</span>
                                <span class="text-[12px] {{ $task['state'] === 'ok' ? 'text-ink-grey' : \App\Support\Swatch::text($color).' font-medium' }}">{{ $task['text'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    @if ($timeline->isNotEmpty())
        <section class="border-t border-line pt-4">
            <div class="text-[13px] font-semibold text-[#6C6658] uppercase tracking-wide mb-3">Déroulé du jour — {{ now()->locale('fr')->translatedFormat('l j F') }}</div>
            <div class="flex gap-2.5 overflow-x-auto pb-1">
                @foreach ($timeline as $t)
                    <a href="{{ route('work-orders.show', $t['id']) }}" class="flex-shrink-0 w-[196px] flex flex-col gap-1.5 px-[13px] py-3 border border-line-soft border-l-[3px] border-l-gold rounded-[10px] bg-white">
                        <span class="font-mono text-[12px] text-[#6C6658]">{{ $t['time'] }}</span>
                        <span class="text-[13px] font-semibold leading-snug">{{ $t['title'] }}</span>
                        <span class="text-[11.5px] text-ink-grey">{{ $t['who'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
