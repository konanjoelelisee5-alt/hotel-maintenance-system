@php
    $search = request('search');
    $tabLabels = ['all' => 'Catalogue', 'low' => 'Sous le seuil', 'inactive' => 'Retirées'];
    $tabItems = collect($tabLabels)->map(fn ($label, $key) => [
        'key' => $key, 'label' => $label, 'count' => $counts[$key],
        'href' => route('parts.index', array_filter(['tab' => $key === 'all' ? null : $key, 'search' => $search], 'filled')),
    ])->values()->all();
    $kpis = [
        ['label' => 'Références', 'value' => $stats['references'], 'sub' => 'pièces au catalogue', 'dot' => 'bg-navy', 'href' => route('parts.index'), 'active' => $tab === 'all'],
        ['label' => 'Sous le seuil', 'value' => $stats['low'], 'sub' => 'à commander', 'dot' => 'bg-red', 'href' => route('parts.index', ['tab' => 'low']), 'active' => $tab === 'low'],
        ['label' => 'Valeur du stock', 'value' => \App\Support\Money::format($stats['value']), 'sub' => 'quantités × coût unitaire', 'dot' => 'bg-gold'],
        ['label' => 'Réservées', 'value' => $stats['reserved'], 'sub' => 'unités promises à des OT', 'dot' => 'bg-blue'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats'" :page-title="'Pièces & stock'">
    <x-slot:primaryAction>
        <a href="{{ route('parts.create') }}" data-modal class="btn btn-primary">+ Nouvelle pièce</a>
    </x-slot:primaryAction>

    <x-kpi-band :items="$kpis" tiles />

    <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
        <div class="flex items-end gap-4 flex-wrap px-4 sm:px-6 pt-5 border-b border-line">
            <div class="flex flex-col gap-0.5 pb-3.5">
                <h2 class="m-0 text-[17px] font-semibold text-navy">{{ ['all' => 'Pièces au catalogue', 'low' => 'Pièces à commander', 'inactive' => 'Pièces retirées'][$tab] }}</h2>
                <div class="text-[12.5px] text-[#6C6658]">{{ $parts->total() }} pièce(s) · par nom</div>
            </div>
            <x-tabs :items="$tabItems" :active="$tab" label="Filtres" class="sm:ml-auto max-w-full" />
        </div>

        <form method="GET" action="{{ route('parts.index') }}" class="flex items-center gap-2.5 flex-wrap px-4 sm:px-6 py-3.5 bg-paper/60 border-b border-line-soft">
            @if ($tab !== 'all') <input type="hidden" name="tab" value="{{ $tab }}"> @endif
            <label class="flex items-center gap-2 h-[40px] w-full sm:w-[320px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom ou référence (SKU)…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13.5px] placeholder:text-ink-grey focus:ring-0">
            </label>
            @if (filled($search))
                <a href="{{ route('parts.index', array_filter(['tab' => $tab === 'all' ? null : $tab], 'filled')) }}" class="text-[12.5px] font-semibold text-[#6C6658] hover:text-navy">Effacer la recherche</a>
            @endif
        </form>

        @if ($parts->isEmpty())
            <div class="px-5 py-12 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-ink-grey"><x-nav-icon name="part" class="w-5 h-5" /></div>
                <div class="text-[14px] font-semibold">{{ $tab === 'low' ? 'Aucune pièce sous le seuil' : 'Aucune pièce ne correspond' }}</div>
                <div class="text-[12.5px] text-[#6C6658]">{{ $tab === 'low' ? 'Le stock couvre les besoins pour l’instant.' : 'Changez d’onglet ou effacez la recherche.' }}</div>
            </div>
        @else
            <div class="hidden md:grid grid-cols-[minmax(0,1fr)_220px_120px_90px] gap-4 px-6 py-3 bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-[#6C6658]">
                <span>Pièce</span><span>Disponible / en stock</span><span class="text-right">Coût unitaire</span><span></span>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($parts as $part)
                    @php
                        $low = $part->is_active && $part->is_below_threshold;
                        $fill = $part->quantity_on_hand > 0 ? min(100, round($part->quantity_available / max($part->quantity_on_hand, 1) * 100)) : 0;
                    @endphp
                    <li class="relative grid gap-2.5 md:grid-cols-[minmax(0,1fr)_220px_120px_90px] md:gap-4 md:items-center px-4 sm:px-6 py-3.5 hover:bg-paper/60 {{ $low ? 'shadow-[inset_3px_0_0_theme(colors.red)]' : '' }}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('parts.show', $part) }}" class="text-[14.5px] font-semibold text-navy hover:underline after:absolute after:inset-0 md:after:hidden">{{ $part->name }}</a>
                                @if ($low)
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ \App\Support\Swatch::pill('red') }}">Sous le seuil ({{ $part->reorder_threshold }})</span>
                                @endif
                            </div>
                            <div class="font-mono text-[12px] text-[#6C6658] mt-0.5">{{ $part->sku }}</div>
                        </div>
                        <div>
                            <div class="flex items-baseline justify-between gap-2 text-[13px]">
                                <span><strong class="text-navy">{{ $part->quantity_available }}</strong> <span class="text-[#6C6658]">/ {{ $part->quantity_on_hand }} {{ $part->unit }}</span></span>
                                @if ($part->quantity_reserved)
                                    <span class="text-[11.5px] text-[#6C6658]">{{ $part->quantity_reserved }} réservée(s)</span>
                                @endif
                            </div>
                            <div class="mt-1.5 h-[5px] rounded-full bg-line-soft overflow-hidden">
                                <div class="h-full {{ $low ? 'bg-red' : 'bg-green' }}" style="width: {{ $fill }}%"></div>
                            </div>
                        </div>
                        <div class="text-[13px] text-[#4A4639] md:text-right font-mono">{{ \App\Support\Money::format($part->unit_cost) }}</div>
                        <div class="hidden md:block text-right">
                            <a href="{{ route('parts.show', $part) }}" class="btn btn-sm btn-secondary">Ouvrir</a>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($parts->hasPages())
                <div class="px-4 sm:px-6 py-3.5 border-t border-line bg-paper/60">{{ $parts->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
