{{-- File des ordres de travail des accueils de maintenance (admin, manager, technicien), au
     style de la feuille : rond d'état, intervention (lieu et intervenant dessous), statut à
     pastille, délai (SLA), menu. Onglets en pastilles avec compteur. Triés par urgence SLA (WorkQueue).
     $queue, $filters, $filterCounts, $filter, $here (fn (?string $filter) => url), $queueTitle. --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $statusDot = ['ouvert' => 'bg-blue', 'en_cours' => 'bg-amber', 'en_attente' => 'bg-ink-faint', 'resolu' => 'bg-green',
        'rejete' => 'bg-red', 'ferme' => 'bg-green', 'annule' => 'bg-ink-faint'];
@endphp
<section aria-labelledby="queue-title" class="pt-2">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
        <h2 id="queue-title" class="m-0 text-[20px] font-bold tracking-[-0.015em]">{{ $queueTitle }}</h2>
        <span class="hidden sm:block h-5 w-px bg-line" aria-hidden="true"></span>
        <span class="text-[14px] font-semibold text-ink-body">triés par urgence</span>
        <nav class="sm:ml-auto flex items-center gap-1 p-1 rounded-full bg-paper max-w-full overflow-x-auto" aria-label="Filtres">
            @foreach ($filters as $f)
                @php $active = $filter === $f['key']; @endphp
                <a href="{{ $here($f['key']) }}" @if ($active) aria-current="page" @endif
                   class="h-8 px-3.5 rounded-full inline-flex items-center gap-1.5 text-[13px] whitespace-nowrap transition-colors {{ $active ? 'bg-white font-bold text-ink-deep shadow-[0_4px_12px_-8px_rgba(23,25,31,.5)]' : 'font-semibold text-ink-muted hover:text-ink-deep' }}">
                    {{ $f['label'] }}
                    <span class="tabular text-[12px] {{ $active ? 'text-blue' : 'text-ink-grey' }}">{{ $filterCounts[$f['key']] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="mt-3">
        @forelse ($queue as $w)
            @php
                $sla = $w->slaSummary();
                $urgent = $w->priority?->code === 'urgente';
                $done = in_array($w->status, ['resolu', 'ferme'], true);
                $circle = match (true) {
                    $sla['late'] && ! $done => 'bg-danger-soft text-red',
                    $urgent && ! $done => 'bg-[#FDEBDD] text-[#B4561A]',
                    $done => 'bg-ok-bg text-green',
                    default => 'bg-[rgb(var(--rc-sky))] text-[rgb(var(--rc-sky-ink))]',
                };
                $category = $hk::category($w);
            @endphp
            <a href="{{ route('work-orders.show', $w) }}"
               class="group grid grid-cols-[48px_minmax(0,1fr)] desk:grid-cols-[48px_minmax(0,1fr)_116px_minmax(0,150px)_32px] items-center gap-x-4 gap-y-1 py-3 -mx-3 px-3 rounded-2xl hover:bg-paper transition-colors">
                <span class="w-12 h-12 rounded-full flex items-center justify-center {{ $circle }}">
                    <x-hk.icon :name="$hk::categoryIcon($category)" :size="20" />
                </span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="text-[15px] font-semibold truncate">{{ $w->title }}</span>
                        @if ($urgent)<span class="flex-shrink-0 px-2 py-0.5 rounded-full text-[11px] font-bold bg-[#FDEBDD] text-[#B4561A]">Urgent</span>@endif
                    </span>
                    <span class="block text-[12.5px] text-ink-muted truncate">
                        <span class="tabular">{{ $w->code() }}</span> · {{ $w->room?->label ?? 'Sans lieu' }}
                        <span class="hidden desk:inline">·
                            @if ($w->assignee) {{ $w->assignee->name }} @else <span class="font-semibold text-amber">à affecter</span> @endif
                        </span>
                    </span>
                </span>
                <span class="hidden desk:flex flex-col gap-1 min-w-0">
                    <span class="flex items-center gap-2 text-[13.5px] font-medium text-ink-body min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $statusDot[$w->status] ?? 'bg-ink-faint' }}"></span>
                        <span class="truncate">{{ $w->status_label }}</span>
                    </span>
                    <x-ack-badge :work-order="$w" class="!flex w-fit" />
                </span>
                <span class="hidden desk:flex items-center gap-1.5 text-[13px] font-semibold min-w-0 {{ \App\Support\Swatch::text($sla['color']) }}">
                    <x-nav-icon name="clock" class="w-4 h-4 flex-shrink-0 opacity-70" />
                    <span class="truncate max-w-[120px] desk:max-w-none" title="{{ $sla['text'] }}">{{ $sla['text'] }}</span>
                </span>
                <span class="hidden desk:flex w-8 h-8 rounded-full items-center justify-center text-ink-grey group-hover:bg-white group-hover:text-ink-deep transition-colors" aria-hidden="true">
                    <x-nav-icon name="more" class="w-4 h-4" />
                </span>
                <span class="desk:hidden col-start-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12.5px] text-ink-muted">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full {{ $statusDot[$w->status] ?? 'bg-ink-faint' }}"></span>{{ $w->status_label }}</span>
                    <span class="font-semibold {{ \App\Support\Swatch::text($sla['color']) }}">{{ $sla['text'] }}</span>
                    <span>{{ $w->assignee?->name ?? 'À affecter' }}</span>
                </span>
            </a>
        @empty
            <div class="py-12 flex flex-col items-center gap-2 text-center">
                <span class="w-12 h-12 rounded-full bg-ok-bg text-green flex items-center justify-center"><x-nav-icon name="check" class="w-5 h-5" /></span>
                <div class="text-[15px] font-semibold">Aucun ordre dans cet onglet</div>
                <div class="text-[13.5px] text-ink-grey">Rien à traiter ici. Changez d'onglet pour voir le reste.</div>
            </div>
        @endforelse
    </div>

    <a href="{{ route('work-orders.index') }}" class="mt-2 h-10 px-4 rounded-full border border-line inline-flex items-center gap-1.5 text-[13px] font-semibold hover:bg-paper transition-colors">
        Voir tous les ordres
        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
    </a>
</section>
