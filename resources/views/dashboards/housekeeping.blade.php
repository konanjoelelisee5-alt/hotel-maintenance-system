<x-app-layout crumb="Mon étage · housekeeping" page-title="Mes signalements">
    {{-- Accueil housekeeping (agent et gouvernante), habillage de la maquette Claude Design
         (docs/design-claude/). Mêmes données que les autres tableaux de bord
         (DashboardController::render) ; seule la présentation est propre au housekeeping. --}}
    @php
        $user = auth()->user();
        $dashboardRoute = $user->dashboardRoute();
        // Le responsable voit qui a signalé : un seul chargement pour toute la file.
        if ($user->isDepartmentHead()) {
            $queue->loadMissing('reporter');
        }
        // Indicateurs, dans l'ordre de $pulse : icône, fond de tuile, couleur du chiffre.
        $kpiStyles = [
            ['icon' => 'clipboard', 'tile' => 'bg-[#EDF0F4] text-navy', 'value' => 'text-navy'],
            ['icon' => 'clock', 'tile' => 'bg-[#FBE7E5] text-red', 'value' => 'text-red'],
            ['icon' => 'check', 'tile' => 'bg-[#E3F1EA] text-green', 'value' => 'text-green'],
            ['icon' => 'building', 'tile' => 'bg-line-soft text-navy', 'value' => 'text-navy'],
        ];
        $queueTitle = match ($filter) {
            'urgent' => 'Signalements urgents',
            'mine' => 'Mes signalements',
            'unassigned' => "En attente d'affectation",
            default => $user->isDepartmentHead() ? "Signalements de l'équipe" : 'Tous les signalements',
        };
    @endphp

    {{-- Bonjour --}}
    <div class="flex flex-col gap-1">
        <span class="text-[14px] font-medium text-[#5C6472] first-letter:uppercase">{{ now()->locale('fr')->translatedFormat('l j F') }}</span>
        <span class="text-[28px] lg:text-[30px] font-extrabold tracking-[-.025em] leading-[1.12]">Bonjour, {{ \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name }}</span>
    </div>

    {{-- Appel à l'action : le housekeeping signale plutôt qu'il n'exécute. --}}
    @can('create', \App\Models\WorkOrder::class)
        <div class="relative overflow-hidden bg-navy rounded-[22px] p-5 sm:px-7 sm:py-6 flex flex-col sm:flex-row sm:items-center gap-[18px] shadow-[0_20px_36px_-20px_rgba(14,33,54,.7)]">
            {{-- Motif de l'arche, en filigrane. --}}
            <svg class="absolute -right-[26px] -top-[18px] w-[170px] h-[170px] pointer-events-none" viewBox="0 0 40 40" aria-hidden="true">
                <path d="M12 30V19a8 8 0 0 1 16 0v11" stroke="#B58435" stroke-opacity=".22" stroke-width="1.2" fill="none"/>
                <path d="M17 30v-7.5a3 3 0 0 1 6 0V30" stroke="#B58435" stroke-opacity=".22" stroke-width="1" fill="none"/>
            </svg>
            <div class="relative flex-1 flex flex-col gap-1">
                <h2 class="text-[19px] sm:text-[22px] font-bold text-white tracking-[-.01em]">Un problème dans une chambre ?</h2>
                <p class="text-[14px] text-[#A9B6C6] leading-snug">Touchez une image, parlez dans le micro : ça part directement vers la maintenance.</p>
                <a href="{{ route('work-orders.create') }}" data-modal class="self-start mt-1 text-[12.5px] text-[#A9B6C6] underline underline-offset-2 hover:text-white">ou remplir le formulaire détaillé</a>
            </div>
            <a href="{{ route('quick-reports.create') }}"
               class="relative flex items-center justify-center gap-2.5 h-[58px] px-[26px] rounded-2xl bg-gold text-navy text-[17px] font-bold shadow-[inset_0_-2px_0_rgba(0,0,0,.12)] hover:bg-gold-400 active:scale-[.98] transition">
                <x-nav-icon name="mic" class="!w-6 !h-6" />
                Signaler un problème
            </a>
        </div>
    @endcan

    {{-- Indicateurs : une seule bande, chaque case ouvre l'onglet correspondant. --}}
    <div class="bg-white border border-line rounded-[20px] overflow-hidden grid grid-cols-2 sm:grid-cols-4">
        @foreach ($pulse as $i => $p)
            @php
                $k = $kpiStyles[$i] ?? $kpiStyles[0];
                $active = $p['filter'] !== null && $filter === $p['filter'];
            @endphp
            <a href="{{ $p['url'] ?? route($dashboardRoute, ['filter' => $p['filter']]) }}"
               class="flex flex-col lg:flex-row lg:items-center gap-3.5 p-4 sm:px-5 sm:py-[18px] hover:bg-paper transition
                      {{ $i > 0 ? 'sm:border-l' : '' }} {{ $i % 2 ? 'border-l' : '' }} {{ $i >= 2 ? 'border-t sm:border-t-0' : '' }} border-line-soft
                      {{ $active ? 'bg-paper' : '' }}">
                <span class="w-[42px] h-[42px] rounded-xl flex items-center justify-center flex-shrink-0 {{ $k['tile'] }}">
                    <x-nav-icon :name="$k['icon']" class="!w-[22px] !h-[22px]" />
                </span>
                <span class="flex flex-col gap-[3px] min-w-0">
                    <span class="text-[28px] font-extrabold tracking-[-.02em] leading-[1.05] tabular-nums {{ $k['value'] }}">{{ $p['value'] }}</span>
                    <span class="text-[13px] font-medium leading-snug text-[#5C6472]">{{ $p['label'] }}</span>
                </span>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 items-start lg:grid-cols-[minmax(0,1fr)_340px]">

        {{-- File des signalements --}}
        <section class="flex flex-col gap-3 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="flex-1 text-[19px] font-bold tracking-[-.01em] whitespace-nowrap">{{ $queueTitle }}</h2>
                <nav class="flex gap-1 bg-[#EAE5DB] rounded-xl p-1 basis-full sm:basis-auto max-w-full overflow-x-auto" aria-label="Filtrer les signalements">
                    @foreach ($filters as $f)
                        @php $on = $filter === $f['key']; @endphp
                        <a href="{{ route($dashboardRoute, ['filter' => $f['key']]) }}" @if ($on) aria-current="page" @endif
                           class="flex-1 shrink-0 inline-flex items-center justify-center gap-1.5 h-[38px] px-2.5 sm:px-3.5 rounded-[9px] text-[12.5px] sm:text-[13px] font-semibold whitespace-nowrap transition
                                  {{ $on ? 'bg-white text-navy shadow-[0_1px_3px_rgba(14,33,54,.12)]' : 'text-[#5C6472] hover:text-navy' }}">
                            {{ $f['label'] }}
                            <span class="font-mono text-[11px] {{ $on ? 'text-gold-600' : 'text-ink-grey' }}">{{ $filterCounts[$f['key']] ?? 0 }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            @if ($queue->isEmpty())
                <div class="bg-white border border-line rounded-[20px] px-4 py-9 flex flex-col items-center gap-2 text-center">
                    <span class="w-12 h-12 rounded-full bg-[#E3F1EA] text-green flex items-center justify-center"><x-nav-icon name="check" class="!w-6 !h-6" /></span>
                    <span class="text-[15px] font-semibold">Rien à signaler ici</span>
                    <span class="text-[13px] text-ink-grey">Changez d'onglet pour voir les autres signalements.</span>
                </div>
            @else
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    @foreach ($queue as $w)
                        @php $urgent = $w->priority?->code === 'urgente' && ! in_array($w->status, ['resolu', 'ferme', 'annule'], true); @endphp
                        <a href="{{ route('work-orders.show', $w) }}"
                           class="flex items-center gap-3.5 min-w-0 bg-white border border-line rounded-[20px] p-3.5 hover:border-[#CFC6B5] active:scale-[.99] transition">
                            <span class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 {{ $urgent ? 'bg-[#FBE7E5] text-red' : 'bg-line-soft text-navy' }}">
                                <x-nav-icon :name="$urgent ? 'alert' : ($w->room ? 'bed' : 'building')" class="!w-6 !h-6" />
                            </span>
                            <span class="flex-1 min-w-0 flex flex-col gap-1.5">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span class="text-[16px] font-bold tracking-[-.01em] truncate">{{ $w->room?->label ?? 'Parties communes' }}</span>
                                    <span class="font-mono text-[11px] text-ink-grey flex-shrink-0">{{ $w->code() }}</span>
                                </span>
                                <span class="text-[14px] text-[#5C6472] truncate">{{ $w->title }}@if ($user->isDepartmentHead() && $w->reporter) · par {{ $w->reporter->name }}@endif</span>
                                <span class="flex items-center gap-1.5 flex-wrap">
                                    <x-work-order-status-badge :status="$w->status" />
                                    @if ($urgent)
                                        <span class="inline-flex items-center h-6 px-2 rounded-[7px] bg-[#FBE7E5] text-red text-[12px] font-bold">Urgent</span>
                                    @endif
                                </span>
                                <span class="text-[13px] leading-snug text-[#5C6472]">{{ $w->assignee ? $w->assignee->name.' s’en occupe' : "En attente d'un technicien" }}</span>
                            </span>
                            <x-nav-icon name="arrow-right" class="!w-5 !h-5 text-[#B9B3A7] flex-shrink-0" />
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('work-orders.index') }}" class="self-center inline-flex items-center gap-1 h-10 px-3 text-[14px] font-semibold text-gold-600 hover:text-navy">
                    Voir tous les signalements <x-nav-icon name="arrow-right" class="!w-4 !h-4" />
                </a>
            @endif
        </section>

        {{-- Colonne de droite : état des chambres (ou charge de l'équipe), puis suivi. --}}
        <div class="flex flex-col gap-6 min-w-0">
            <section class="bg-white border border-line rounded-[20px] px-[18px] pt-4 pb-1.5">
                <div class="flex items-baseline justify-between gap-2 mb-1">
                    <h2 class="text-[17px] font-bold tracking-[-.01em]">{{ $sideA['title'] }}</h2>
                    <span class="text-[12px] text-ink-grey">{{ $sideA['sub'] ?? '' }}</span>
                </div>
                @forelse ($sideA['items'] as $item)
                    <div class="flex items-center gap-3 py-2.5 border-b border-line-soft last:border-b-0">
                        <span class="flex-1 min-w-0 text-[14px] font-semibold truncate">{{ $item['name'] }}</span>
                        <span class="w-20 h-1.5 rounded-full bg-line-soft overflow-hidden flex-shrink-0">
                            <span class="block h-full rounded-full {{ \App\Support\Swatch::bg($item['color']) }}" style="width: {{ $item['pct'] }}%"></span>
                        </span>
                        <span class="font-mono text-[12px] font-semibold whitespace-nowrap {{ \App\Support\Swatch::text($item['color']) }}">{{ $item['meta'] }}</span>
                    </div>
                @empty
                    <p class="py-3 text-[13px] text-ink-grey">Rien à signaler.</p>
                @endforelse
            </section>

            <section class="bg-white border border-line rounded-[20px] px-[18px] pt-4 pb-1.5">
                <h2 class="text-[17px] font-bold tracking-[-.01em]">{{ $sideB['title'] }}</h2>
                <div class="flex flex-col mt-1.5">
                    @forelse ($sideB['items'] as $item)
                        <div class="flex gap-3 py-2.5 border-b border-line-soft last:border-b-0">
                            <span class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 {{ \App\Support\Swatch::bg($item['color']) }}"></span>
                            <span class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-[13px] leading-snug">{{ $item['label'] }}</span>
                                <span class="font-mono text-[11px] text-ink-grey">{{ $item['meta'] }}</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-3 text-[13px] text-ink-grey">Rien à signaler.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @if ($timeline->isNotEmpty())
        <section class="border-t border-line pt-4">
            <h2 class="mb-3 text-[12px] font-bold tracking-[.1em] uppercase text-ink-grey">Déroulé du jour — {{ now()->locale('fr')->translatedFormat('l j F') }}</h2>
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
