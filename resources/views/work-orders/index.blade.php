@php
    $title = match (true) {
        auth()->user()->role === \App\Enums\UserRole::Technicien => 'Mes ordres de travail',
        auth()->user()->role === \App\Enums\UserRole::Housekeeping => 'Historique de mes signalements',
        auth()->user()->role === \App\Enums\UserRole::Reception => 'Demandes de la réception',
        default => 'Ordres de travail',
    };
@endphp

<x-app-layout crumb="Espace de travail" :page-title="$title">
    @can('create', \App\Models\WorkOrder::class)
        <x-slot:primaryAction>
            <a href="{{ route('work-orders.create') }}" class="px-[17px] py-[10px] border-0 rounded-[9px] bg-navy text-white text-[13.5px] font-semibold inline-block">
                + Nouvel ordre
            </a>
        </x-slot:primaryAction>
    @endcan

    <div class="flex gap-3 overflow-x-auto pb-1 -mx-4 px-4 lg:mx-0 lg:px-0">
        @foreach ($stats as $s)
            <x-mobile-metric-chip :label="$s['label']" :value="$s['value']" color="blue" />
        @endforeach
    </div>

    <section class="bg-white border border-line rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 flex-wrap px-[18px] py-[15px] border-b border-line-soft">
            <form action="{{ route('work-orders.index') }}" method="GET" class="flex items-center gap-2 px-3 py-2 border border-line rounded-[9px] bg-paper w-full sm:w-auto">
                <span class="text-[13px] text-[#A09A8C]">⌕</span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Chambre, titre…" class="border-0 outline-none bg-transparent text-[13px] w-full sm:w-[180px] p-0 focus:ring-0">
                <input type="hidden" name="filter" value="{{ $filter }}">
            </form>
            <div class="flex gap-1.5 flex-wrap">
                @foreach ($filters as $f)
                    <a href="{{ route('work-orders.index', array_filter(['filter' => $f['key'], 'q' => $q])) }}" class="px-3 py-1.5 rounded-full border text-[12px] font-semibold min-h-[36px] flex items-center {{ $filter === $f['key'] ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line text-[#26496B]' }}">{{ $f['label'] }}</a>
                @endforeach
            </div>
        </div>

        @if ($workOrders->isEmpty())
            <div class="px-5 py-14 flex flex-col items-center gap-2.5 text-center">
                <div class="w-[46px] h-[46px] rounded-full bg-line-soft flex items-center justify-center text-[#A09A8C] text-[18px]">☰</div>
                <div class="text-[14.5px] font-semibold">Aucun ordre ne correspond au filtre</div>
                <div class="text-[12.5px] text-ink-grey max-w-[400px] leading-relaxed">Élargissez le filtre ou créez un ordre.</div>
            </div>
        @else
            <div class="flex items-center justify-between gap-2 px-[18px] py-2.5 text-[11.5px] text-ink-grey border-b border-line-soft">
                <span>{{ $workOrders->total() }} ordre(s) · triés par {{ mb_strtolower($sortOptions[$sort]) }}</span>
                <form action="{{ route('work-orders.index') }}" method="GET" class="flex items-center gap-1.5">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <x-nav-icon name="sort" class="w-3.5 h-3.5 text-[#6C6658]" />
                    <select name="sort" onchange="this.form.submit()" class="border-0 bg-transparent text-[11.5px] font-semibold text-[#26496B] focus:ring-0 py-0 pr-6">
                        @foreach ($sortOptions as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>Trier : {{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Mobile / tablette : cartes empilées (un tableau serait illisible en dessous de 1024px) --}}
            <div class="lg:hidden flex flex-col gap-2.5 p-3">
                @foreach ($workOrders as $w)
                    @include('work-orders.partials._ot-card', ['w' => $w, 'showDueDate' => true])
                @endforeach
            </div>

            {{-- Desktop : tableau classique --}}
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full border-collapse min-w-[760px]">
                    <thead>
                        <tr>
                            @foreach (['Ordre', 'Chambre / équipement', 'Affecté à', 'Priorité', 'SLA', 'Statut'] as $col)
                                <th class="text-left px-[18px] py-[11px] bg-paper border-b border-line-soft text-[11.5px] font-semibold text-[#7D7768] whitespace-nowrap">{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workOrders as $w)
                            <tr onclick="window.location='{{ route('work-orders.show', $w) }}'" class="cursor-pointer border-b border-line-soft hover:bg-paper">
                                <td class="px-[18px] py-3 align-top">
                                    <span class="text-[13px] font-semibold">{{ $w->title }}</span>
                                    <span class="block text-[11.5px] text-ink-grey mt-0.5">{{ $w->code() }}</span>
                                </td>
                                <td class="px-[18px] py-3 align-top text-[13px]">
                                    Chambre {{ $w->room?->number ?? '—' }}
                                    <span class="block text-[11.5px] text-ink-grey mt-0.5">{{ $w->equipment?->name ?? '—' }}</span>
                                </td>
                                <td class="px-[18px] py-3 align-top text-[13px]">
                                    {{ $w->assignee?->name ?? '— à affecter' }}
                                    <span class="block text-[11.5px] text-ink-grey mt-0.5">{{ $w->type->label }}</span>
                                </td>
                                <td class="px-[18px] py-3 align-top">
                                    <x-work-order-priority-badge :priority="$w->priority" />
                                </td>
                                <td class="px-[18px] py-3 align-top">
                                    <span class="text-[13px] font-semibold {{ \App\Support\Swatch::text($w->slaColorClass()) }}">{{ $w->slaRemainingLabel() }}</span>
                                </td>
                                <td class="px-[18px] py-3 align-top">
                                    <x-work-order-status-badge :status="$w->status" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-[18px] py-3.5 border-t border-line-soft">
                {{ $workOrders->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
