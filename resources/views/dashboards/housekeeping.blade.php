{{-- Accueil Housekeeping (agent et gouvernante), avec les blocs du tableau de bord admin :
     bande d'indicateurs, panneau à onglets soulignés, cartes « barres » et « liste ».
     Données : DashboardController::render() — pulse, queue + filters, sideA (charge de
     l'équipe, gouvernante), sideB (activité récente), toConfirm. --}}
@php
    $user = auth()->user();
    $hk = \App\Support\Housekeeping::class;
    $isHead = $user->isDepartmentHead();
    $here = fn (?string $f) => route('housekeeping.dashboard', ['filter' => $f]);
    $tabLabels = ['mine' => 'Les miens', 'urgent' => 'Urgents', 'all' => $isHead ? "Toute l'équipe" : 'Tous'];
    $listTitle = match ($filter) {
        'urgent' => 'Signalements urgents',
        'unassigned' => "En attente d'un technicien",
        'all' => $isHead ? "Signalements de l'équipe" : 'Tous mes signalements',
        default => 'Mes signalements',
    };
@endphp

<x-app-layout :crumb="'Mon espace · '.ucfirst(now()->locale('fr')->translatedFormat('l j F'))" page-title="Accueil">
    {{-- desktop-only : sur téléphone, la carte marine ci-dessous porte déjà ce bouton. --}}
    <x-slot:primaryAction desktop-only>
        <a href="{{ route('quick-reports.create') }}" class="btn btn-gold"><x-nav-icon name="mic" /> Signaler un problème</a>
    </x-slot:primaryAction>

    {{-- Signalement : l'action principale du service, toujours en tête sur téléphone. --}}
    <section class="tab:hidden bg-navy rounded-xl p-4 flex flex-col gap-3.5">
        <div class="flex items-start gap-3">
            <span class="w-10 h-10 rounded-[10px] border border-gold/40 bg-white/[.06] text-gold flex items-center justify-center flex-shrink-0"><x-hk.icon name="megaphone" /></span>
            <div class="min-w-0">
                <h2 class="m-0 text-white text-[15.5px] font-semibold">Une panne à signaler ?</h2>
                <p class="m-0 mt-0.5 text-[12.5px] text-[#B9C7D6] leading-relaxed">Lieu, problème, message vocal : la maintenance est prévenue tout de suite.</p>
            </div>
        </div>
        <a href="{{ route('quick-reports.create') }}" class="btn btn-gold btn-lg w-full !h-[52px]"><x-nav-icon name="mic" /> Signaler un problème</a>
    </section>

    {{-- Réparations à confirmer par le service demandeur --}}
    @if ($toConfirm->isNotEmpty())
        <section class="bg-white border border-line rounded-xl overflow-hidden" x-data="{ all: false }">
            <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
                <span class="w-8 h-8 rounded-[8px] border border-green/20 bg-[#E6F3EC] text-green flex items-center justify-center flex-shrink-0"><x-hk.icon name="check-circle" :size="16" /></span>
                <div class="min-w-0 flex-1">
                    <h2 class="m-0 text-[14.5px] font-semibold text-navy">À confirmer <span class="font-mono text-[12px] text-ink-grey font-normal">{{ $toConfirm->count() }}</span></h2>
                    <p class="m-0 text-[12.5px] text-[#6C6658]">Réparations terminées : vérifiez sur place puis répondez.</p>
                </div>
            </header>
            @foreach ($toConfirm as $w)
                <a href="{{ route('work-orders.show', $w) }}" @if ($loop->index >= 3) x-show="all" x-cloak @endif
                   class="flex items-center gap-3 px-5 py-3 border-b border-line-soft last:border-b-0 hover:bg-paper">
                    <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                        <span class="text-[14px] font-semibold text-navy truncate">{{ $w->room?->label ?? '—' }} · {{ $w->title }}</span>
                        <span class="text-[12.5px] text-[#6C6658] truncate">Réparé {{ $w->completed_at?->locale('fr')->diffForHumans() }}{{ $w->assignee ? ' par '.$w->assignee->name : '' }}</span>
                    </span>
                    <span class="btn btn-sm btn-secondary flex-shrink-0">Répondre</span>
                </a>
            @endforeach
            @if ($toConfirm->count() > 3)
                <button type="button" x-show="! all" @click="all = true" class="w-full h-11 text-[13px] font-semibold text-navy bg-paper/60 border-t border-line-soft hover:underline">
                    Afficher les {{ $toConfirm->count() - 3 }} autres
                </button>
            @endif
        </section>
    @endif

    {{-- Indicateurs : la bande de la Supervision (tuiles 2 × 2 sur téléphone). --}}
    {{-- Libellés courts : ils tiennent dans une tuile de téléphone sans être coupés. --}}
    @php
        $kpiLabels = [
            [$isHead ? "Ouverts (équipe)" : 'Ouverts', 'signalements en cours'],
            ['Sans technicien', "en attente d'affectation"],
            ['Résolus en 7 jours', 'réparations clôturées'],
            ['Chambres suivies', 'avec un signalement'],
        ];
    @endphp
    <x-kpi-band tiles :items="collect($pulse)->values()->map(fn (array $p, int $i) => [
        'label' => $kpiLabels[$i][0] ?? $p['label'],
        'value' => $p['value'],
        'sub' => $kpiLabels[$i][1] ?? $p['sub'],
        'dot' => \App\Support\Swatch::bg($p['color'] ?? 'navy'),
        'href' => $here($p['filter']),
    ])->all()" />

    <div class="grid gap-5 items-start desk:grid-cols-[minmax(0,1fr)_340px]">
        {{-- Signalements --}}
        <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
            <div class="flex items-end gap-4 flex-wrap px-4 tab:px-6 pt-5 border-b border-line">
                <div class="flex flex-col gap-0.5 pb-3.5">
                    <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $listTitle }}</h2>
                    <div class="text-[12.5px] text-ink-grey">{{ $queue->count() }} signalement(s) · les plus récents d'abord</div>
                </div>
                <x-tabs :items="collect($filters)->map(fn ($f) => ['key' => $f['key'], 'label' => $tabLabels[$f['key']] ?? $f['label'], 'count' => $filterCounts[$f['key']] ?? 0, 'href' => $here($f['key'])])->all()"
                        :active="$filter" label="Filtres" class="sm:ml-auto max-w-full" />
            </div>

            @if ($queue->isEmpty())
                <div class="px-5 py-11 flex flex-col items-center gap-2 text-center">
                    <span class="w-[42px] h-[42px] rounded-full bg-[#E6F3EC] text-green flex items-center justify-center"><x-hk.icon name="check" :size="18" /></span>
                    <div class="text-[14px] font-semibold">Aucun signalement dans cet onglet</div>
                    <div class="text-[12.5px] text-ink-grey max-w-[340px] leading-relaxed">Une panne ? Utilisez « Signaler un problème ».</div>
                </div>
            @else
                {{-- Téléphone et tablette : cartes --}}
                <div class="desk:hidden grid grid-cols-1 tab:grid-cols-2 gap-3 p-3 tab:p-4">
                    @foreach ($queue as $w)
                        <x-hk.ot-card :w="$w" :href="route('work-orders.show', $w)" :reporter="$isHead" />
                    @endforeach
                </div>

                {{-- Ordinateur : tableau --}}
                <div class="hidden desk:block">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">
                                <th class="pl-6 pr-3 py-3 w-[108px]">Réf.</th>
                                <th class="px-3 py-3">Lieu et problème</th>
                                <th class="px-3 py-3 w-[150px]">Statut</th>
                                <th class="px-3 py-3 w-[170px]">Technicien</th>
                                <th class="pl-3 pr-6 py-3 w-[110px] text-right">Signalé</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($queue as $w)
                                @php $category = $hk::category($w); $urgent = $w->priority?->code === 'urgente'; @endphp
                                <tr class="border-b border-line-soft last:border-b-0 hover:bg-paper/60 {{ $urgent && $hk::group($w->status) === 'pending' ? 'shadow-[inset_3px_0_0_theme(colors.red)]' : '' }}">
                                    <td class="pl-6 pr-3 py-3.5 align-middle font-mono text-[12.5px] text-[#4A4639] whitespace-nowrap">
                                        <a href="{{ route('work-orders.show', $w) }}" class="hover:text-navy">{{ $w->code() }}</a>
                                    </td>
                                    <td class="px-3 py-3.5 align-middle">
                                        <a href="{{ route('work-orders.show', $w) }}" class="text-[14px] font-semibold text-navy hover:underline">{{ $w->room?->label ?? 'Parties communes' }}</a>
                                        <div class="text-[12.5px] text-[#6C6658] mt-0.5 leading-snug">
                                            <x-hk.icon :name="$hk::categoryIcon($category)" :size="14" class="inline-block align-[-2px] mr-1 text-gold" />{{ $category ? $hk::categoryLabel($category) : $w->title }}
                                        </div>
                                        @if ($isHead && $w->reporter)
                                            <div class="text-[12px] text-ink-grey mt-0.5">Signalé par {{ $w->reporter->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3.5 align-middle"><x-hk.status :status="$w->status" :urgent="$urgent" /></td>
                                    <td class="px-3 py-3.5 align-middle">
                                        @if ($w->assignee)
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span class="w-7 h-7 rounded-full bg-gold flex items-center justify-center text-[10.5px] font-bold text-navy flex-shrink-0">{{ $w->assignee->initialsOrGenerated() }}</span>
                                                <span class="text-[13px] truncate max-w-[130px]" title="{{ $w->assignee->name }}">{{ $w->assignee->name }}</span>
                                            </span>
                                        @else
                                            <span class="text-[13px] text-[#A09A8C]">— en attente</span>
                                        @endif
                                    </td>
                                    <td class="pl-3 pr-6 py-3.5 align-middle text-right font-mono text-[12px] text-ink-grey whitespace-nowrap">{{ $w->created_at->format('d/m H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="px-6 py-3.5 border-t border-line bg-paper/60 text-right">
                <a href="{{ route('work-orders.index') }}" class="text-[13px] font-semibold text-navy hover:underline">Voir tous les signalements →</a>
            </div>
        </section>

        {{-- Colonne latérale : pannes récurrentes partout, charge et activité sur ordinateur --}}
        <div class="flex flex-col gap-5 min-w-0">
            @if ($repeats->isNotEmpty())
                <section class="bg-white border border-line rounded-xl px-6 py-5">
                    <div class="flex items-baseline justify-between gap-3 pb-3.5 border-b border-line-soft">
                        <h2 class="m-0 text-[17px] font-semibold text-navy">Pannes récurrentes</h2>
                        <span class="text-[12.5px] text-ink-grey whitespace-nowrap">{{ \App\Support\Housekeeping::REPEAT_DAYS }} derniers jours</span>
                    </div>
                    <div class="flex flex-col">
                        @foreach ($repeats as $r)
                            <a href="{{ route('work-orders.show', $r['last']) }}" class="flex items-center gap-3 py-3 border-b border-line-soft last:border-b-0 last:pb-0 group">
                                <span class="w-8 h-8 rounded-[8px] border border-amber/30 bg-[#FBF1DF] text-amber flex items-center justify-center flex-shrink-0"><x-hk.icon :name="$r['icon']" :size="16" /></span>
                                <span class="flex flex-col min-w-0 flex-1">
                                    <span class="text-[13.5px] font-semibold text-navy truncate group-hover:underline">{{ $r['place'] }}</span>
                                    <span class="text-[12px] text-[#6C6658]">{{ $r['category'] }}</span>
                                </span>
                                <span class="font-mono text-[12px] font-semibold text-amber whitespace-nowrap">{{ $r['count'] }} fois</span>
                            </a>
                        @endforeach
                    </div>
                    <p class="m-0 mt-3 text-[12px] text-ink-grey leading-relaxed">Une panne qui revient mérite une remise en état complète : signalez-le au chef de maintenance.</p>
                </section>
            @endif

            <div class="hidden desk:flex flex-col gap-5 min-w-0">
                @if ($isHead)
                    @include('dashboards.partials._bars-card', ['panel' => ['title' => "Charge de l'équipe", 'sub' => 'ouverts par agent', 'items' => $sideA['items']]])
                @endif
                @include('dashboards.partials._list-card', ['panel' => [
                    'title' => 'Activité récente',
                    'items' => collect($sideB['items'])->map(fn ($i) => $i + ['who' => $i['meta']]),
                ]])
            </div>
        </div>
    </div>
</x-app-layout>
