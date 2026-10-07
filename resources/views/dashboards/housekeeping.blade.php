{{-- Accueil Housekeeping (agent et gouvernante), dans layouts.sheet comme la réception :
     - au centre : bonjour + date, le grand bouton « Signaler un problème », les repères, la
       courbe des signalements sur 7 jours, la liste des signalements (onglets), les réparations
       à confirmer ;
     - à droite : l'astreinte à appeler, l'activité, et pour la gouvernante les pannes
       récurrentes et la charge de l'équipe.
     Données : DashboardController::render() (pulse, queue, filters, toConfirm, repeats) et
     DashboardController::housekeeping() (chart, activity, onCall, teamLoad). --}}
@php
    $user = auth()->user();
    $hk = \App\Support\Housekeeping::class;
    $isHead = $user->isDepartmentHead();
    $firstName = \Illuminate\Support\Str::of($user->name)->before(' ');
    $here = fn (?string $f) => route('housekeeping.dashboard', ['filter' => $f]);
    $tabLabels = ['mine' => 'Les miens', 'urgent' => 'Urgents', 'all' => $isHead ? "Toute l'équipe" : 'Tous'];
    $listTitle = match ($filter) {
        'urgent' => 'Signalements urgents',
        'unassigned' => "En attente d'un technicien",
        'all' => $isHead ? "Signalements de l'équipe" : 'Tous mes signalements',
        default => 'Mes signalements',
    };
    $open = $pulse[0]['value'] ?? 0;

    // Trois repères (les chiffres de DashboardPanels::pulse), chacun ouvre la liste filtrée.
    $kpis = collect([
        ['label' => $isHead ? 'Ouverts (équipe)' : 'Ouverts', 'icon' => 'clipboard', 'p' => $pulse[0] ?? null,
            'trend' => fn ($v) => null, 'tone' => 'neutral'],
        ['label' => 'Sans technicien', 'icon' => 'wrench', 'p' => $pulse[1] ?? null,
            'trend' => fn ($v) => $v ? 'à affecter' : null, 'tone' => 'down'],
        ['label' => 'Réparés en 7 jours', 'icon' => 'check', 'p' => $pulse[2] ?? null,
            'trend' => fn ($v) => $v ? 'clôturés' : null, 'tone' => 'up'],
    ])->filter(fn ($k) => $k['p'])->map(fn ($k) => [
        'label' => $k['label'], 'icon' => $k['icon'], 'value' => $k['p']['value'],
        'trend' => ($k['trend'])($k['p']['value']), 'tone' => $k['tone'], 'href' => $here($k['p']['filter']),
    ])->values()->all();

    // Couleur du rond de catégorie selon l'état (en attente, en cours, réparé).
    $circle = ['pending' => 'bg-[#FDEBDD] text-[#B4561A]', 'progress' => 'bg-[rgb(var(--rc-sky))] text-[rgb(var(--rc-sky-ink))]', 'done' => 'bg-ok-bg text-green'];
@endphp

<x-app-layout page-title="Accueil">
    <x-slot:greeting>
        <h1 class="m-0 text-[28px] tab:text-[32px] font-bold tracking-[-0.025em] leading-tight">Bonjour, {{ $firstName }}</h1>
        <p class="m-0 mt-2 text-[15px] text-ink-muted">
            <span class="font-semibold text-ink-body first-letter:uppercase inline-block">{{ now()->locale('fr')->isoFormat('dddd D MMMM') }}</span> ·
            @if ($open === 0)
                Aucun signalement en cours{{ $isHead ? ' dans l\'équipe' : '' }}.
            @else
                {{ $open }} signalement{{ $open > 1 ? 's' : '' }} en cours{{ $isHead ? ' dans l\'équipe' : '' }}.
            @endif
        </p>
    </x-slot:greeting>

    {{-- Signaler : l'action principale du service, en tête et en grand (la barre de
         recherche de la maquette). --}}
    <a href="{{ route('quick-reports.create') }}"
       class="group flex items-center gap-4 h-[68px] pl-3 pr-3 sm:pr-6 rounded-full bg-[rgb(var(--rc-sky))] hover:bg-[rgb(205_225_251)] transition-colors">
        <span class="w-12 h-12 rounded-full bg-ink-deep text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
            <x-nav-icon name="mic" class="w-5 h-5" />
        </span>
        <span class="flex-1 min-w-0">
            <span class="block text-[16.5px] font-bold text-[rgb(var(--rc-sky-ink))]">Signaler un problème</span>
            <span class="block text-[13px] text-[rgb(var(--rc-sky-ink))]/80 truncate">Lieu, problème, message vocal : la maintenance est prévenue tout de suite.</span>
        </span>
        <svg viewBox="0 0 24 24" class="hidden sm:block w-5 h-5 text-[rgb(var(--rc-sky-ink))] group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </a>

    {{-- Gouvernante sur téléphone : ses outils (ailleurs, ils sont dans le menu). --}}
    @if ($isHead)
        <nav class="tab:hidden grid grid-cols-3 gap-2" aria-label="Outils de la gouvernante">
            @foreach ($hk::headTools() as $tool)
                <a href="{{ route($tool['route']) }}" class="rounded-[20px] bg-paper px-2 py-3.5 flex flex-col items-center gap-2 text-center">
                    <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center"><x-nav-icon :name="$tool['icon']" class="w-[18px] h-[18px]" /></span>
                    <span class="text-[12.5px] font-semibold leading-tight">{{ $tool['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    {{-- Réparations à confirmer par le service demandeur --}}
    @if ($toConfirm->isNotEmpty())
        <section aria-labelledby="confirm-title" class="rounded-[24px] bg-paper px-5 tab:px-6 py-5" x-data="{ all: false }">
            <h2 id="confirm-title" class="m-0 text-[17px] font-bold">À confirmer · {{ $toConfirm->count() }}</h2>
            <p class="m-0 mt-0.5 text-[13.5px] text-ink-muted">Réparations terminées : vérifiez sur place puis répondez.</p>
            <div class="mt-3 flex flex-col gap-2">
                @foreach ($toConfirm as $w)
                    <a href="{{ route('work-orders.show', $w) }}" @if ($loop->index >= 3) x-show="all" x-cloak @endif
                       class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white hover:shadow-[0_10px_24px_-16px_rgba(23,25,31,.4)] transition-shadow">
                        <span class="w-9 h-9 rounded-full bg-ok-bg text-green flex items-center justify-center flex-shrink-0"><x-nav-icon name="check" class="w-4 h-4" /></span>
                        <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                            <span class="text-[14.5px] font-semibold truncate">{{ $w->room?->label ?? '—' }} · {{ $w->title }}</span>
                            <span class="text-[12.5px] text-ink-muted truncate">Réparé {{ $w->completed_at?->locale('fr')->diffForHumans() }}{{ $w->assignee ? ' par '.$w->assignee->name : '' }}</span>
                        </span>
                        <span class="btn btn-sm btn-secondary flex-shrink-0">Répondre</span>
                    </a>
                @endforeach
            </div>
            @if ($toConfirm->count() > 3)
                <button type="button" x-show="! all" @click="all = true" class="mt-3 text-[13px] font-semibold text-blue hover:underline">
                    Afficher les {{ $toConfirm->count() - 3 }} autres
                </button>
            @endif
        </section>
    @endif

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-chart', ['chartTitle' => $isHead ? "Signalements de l'équipe" : 'Mes signalements'])

    {{-- Signalements (le « Current Tasks » de la maquette) --}}
    <section aria-labelledby="queue-title" class="pt-2">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <h2 id="queue-title" class="m-0 text-[20px] font-bold tracking-[-0.015em]">{{ $listTitle }}</h2>
            <span class="h-5 w-px bg-line" aria-hidden="true"></span>
            <span class="text-[14px] font-semibold text-ink-body">les plus récents</span>
            <nav class="ml-auto flex items-center gap-1 p-1 rounded-full bg-paper max-w-full overflow-x-auto" aria-label="Filtres">
                @foreach ($filters as $f)
                    @php $active = $filter === $f['key']; @endphp
                    <a href="{{ $here($f['key']) }}" @if ($active) aria-current="page" @endif
                       class="h-8 px-3.5 rounded-full inline-flex items-center gap-1.5 text-[13px] whitespace-nowrap transition-colors {{ $active ? 'bg-white font-bold text-ink-deep shadow-[0_4px_12px_-8px_rgba(23,25,31,.5)]' : 'font-semibold text-ink-muted hover:text-ink-deep' }}">
                        {{ $tabLabels[$f['key']] ?? $f['label'] }}
                        <span class="tabular text-[12px] {{ $active ? 'text-blue' : 'text-ink-grey' }}">{{ $filterCounts[$f['key']] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="mt-3">
            @forelse ($queue as $w)
                @php
                    $category = $hk::category($w);
                    $status = $hk::status($w->status);
                    $urgent = $w->priority?->code === 'urgente';
                    $late = $w->slaSummary()['late'];
                @endphp
                <a href="{{ route('work-orders.show', $w) }}"
                   class="group grid grid-cols-[48px_minmax(0,1fr)_auto] sm:grid-cols-[48px_minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,1fr)_32px] items-center gap-x-4 gap-y-1 py-3 -mx-3 px-3 rounded-2xl hover:bg-paper transition-colors">
                    <span class="w-12 h-12 rounded-full flex items-center justify-center {{ $circle[$status['key']] ?? 'bg-paper' }}">
                        <x-hk.icon :name="$hk::categoryIcon($category)" :size="20" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold truncate">{{ $w->room?->label ?? 'Parties communes' }} · {{ $category ? $hk::categoryLabel($category) : $w->title }}</span>
                        <span class="block text-[12.5px] truncate {{ $urgent || $late ? 'font-semibold text-red' : 'text-ink-muted' }}">
                            @if ($urgent && $late) Urgent · en retard
                            @elseif ($urgent) Urgent
                            @elseif ($late) En retard
                            @elseif ($isHead && $w->reporter) Signalé par {{ $w->reporter->name }}
                            @else {{ $w->code() }}
                            @endif
                        </span>
                    </span>
                    <span class="hidden sm:flex items-center gap-2 text-[13.5px] font-medium text-ink-body min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $status['dot'] }}"></span>
                        <span class="truncate">{{ $status['label'] }}</span>
                    </span>
                    <span class="hidden sm:flex items-center gap-2 text-[13.5px] min-w-0">
                        @if ($w->assignee)
                            <span class="w-7 h-7 rounded-full bg-paper flex items-center justify-center text-[10.5px] font-bold flex-shrink-0">{{ $w->assignee->initialsOrGenerated() }}</span>
                            <span class="truncate font-medium">{{ $w->assignee->name }}</span>
                        @else
                            <span class="text-ink-faint">Pas encore de technicien</span>
                        @endif
                    </span>
                    <span class="flex sm:hidden items-center gap-1.5 text-[12.5px] font-semibold text-ink-body justify-self-end">
                        <span class="w-2 h-2 rounded-full {{ $status['dot'] }}"></span>{{ $status['label'] }}
                    </span>
                    <span class="hidden sm:flex w-8 h-8 rounded-full items-center justify-center text-ink-grey group-hover:bg-white group-hover:text-ink-deep transition-colors" aria-hidden="true">
                        <x-nav-icon name="more" class="w-4 h-4" />
                    </span>
                </a>
            @empty
                <div class="py-12 flex flex-col items-center gap-2 text-center">
                    <span class="w-12 h-12 rounded-full bg-ok-bg text-green flex items-center justify-center"><x-nav-icon name="check" class="w-5 h-5" /></span>
                    <div class="text-[15px] font-semibold">Aucun signalement dans cet onglet</div>
                    <div class="text-[13.5px] text-ink-grey">Une panne ? Utilisez « Signaler un problème ».</div>
                </div>
            @endforelse
        </div>

        <a href="{{ route('work-orders.index') }}" class="mt-2 h-10 px-4 rounded-full border border-line inline-flex items-center gap-1.5 text-[13px] font-semibold hover:bg-paper transition-colors">
            Voir tous les signalements
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
        </a>
    </section>

    {{-- Colonne de droite --}}
    <x-slot:aside>
        @include('dashboards.partials.sheet-oncall')

        @include('dashboards.partials.sheet-activity')

        {{-- Gouvernante : une panne qui revient mérite une remise en état complète. --}}
        @if ($repeats->isNotEmpty())
            <section class="rounded-[22px] border border-line px-4 py-4" aria-labelledby="repeats-title">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 id="repeats-title" class="m-0 text-[15px] font-bold">Pannes récurrentes</h2>
                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap">{{ $hk::REPEAT_DAYS }} derniers jours</span>
                </div>
                @foreach ($repeats as $r)
                    <a href="{{ route('work-orders.show', $r['last']) }}" class="mt-3 flex items-center gap-3 group">
                        <span class="w-9 h-9 rounded-full bg-warn-bg text-amber flex items-center justify-center flex-shrink-0"><x-hk.icon :name="$r['icon']" :size="16" /></span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-[13.5px] font-semibold truncate group-hover:underline">{{ $r['place'] }}</span>
                            <span class="block text-[12.5px] text-ink-muted truncate">{{ $r['category'] }}</span>
                        </span>
                        <span class="text-[13px] font-bold text-amber whitespace-nowrap tabular">{{ $r['count'] }} fois</span>
                    </a>
                @endforeach
                <p class="m-0 mt-3 text-[12.5px] text-ink-grey leading-relaxed">Signalez-la au chef de maintenance pour une remise en état complète.</p>
            </section>
        @endif

        {{-- Gouvernante : qui a le plus de signalements ouverts --}}
        @if ($teamLoad->isNotEmpty())
            @php $most = max(1, $teamLoad->max('count')); @endphp
            <section class="rounded-[22px] border border-line px-4 py-4" aria-labelledby="load-title">
                <h2 id="load-title" class="m-0 text-[15px] font-bold">Charge de l'équipe</h2>
                <p class="m-0 text-[12.5px] text-ink-grey">Signalements ouverts par agent</p>
                @foreach ($teamLoad as $row)
                    <div class="mt-3">
                        <div class="flex items-baseline justify-between gap-3 text-[13.5px]">
                            <span class="font-semibold truncate">{{ $row['user']?->name ?? '—' }}</span>
                            <span class="font-bold tabular">{{ $row['count'] }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-paper overflow-hidden">
                            <div class="h-full rounded-full bg-blue" style="width: {{ round($row['count'] / $most * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    </x-slot:aside>
</x-app-layout>
