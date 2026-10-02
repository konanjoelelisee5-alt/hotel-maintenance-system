@php
    $search = request('search');
    $tabLabels = ['all' => 'Toutes', 'open' => 'En cours', 'received' => 'Réceptionnées', 'invoiced' => 'Facturées', 'cancelled' => 'Annulées'];
    $tabItems = collect($tabLabels)->map(fn ($label, $key) => [
        'key' => $key, 'label' => $label, 'count' => $counts[$key],
        'href' => route('purchase-orders.index', array_filter(['tab' => $key === 'all' ? null : $key, 'search' => $search], 'filled')),
    ])->values()->all();
    $kpis = [
        ['label' => 'Commandes en cours', 'value' => $stats['open'], 'sub' => 'du brouillon à la réception', 'dot' => 'bg-blue', 'href' => route('purchase-orders.index', ['tab' => 'open']), 'active' => $tab === 'open'],
        ['label' => 'Montant engagé', 'value' => \App\Support\Money::format($stats['committed']), 'sub' => 'commandes en cours', 'dot' => 'bg-gold'],
        ['label' => 'À réceptionner', 'value' => $stats['toReceive'], 'sub' => 'envoyées ou confirmées', 'dot' => 'bg-amber'],
        ['label' => 'Achats du mois', 'value' => \App\Support\Money::format($stats['monthSpend']), 'sub' => 'réceptionnés ou facturés', 'dot' => 'bg-green'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats'" :page-title="'Bons de commande'">
    <x-slot:primaryAction>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">+ Nouveau bon de commande</a>
    </x-slot:primaryAction>

    <x-kpi-band :items="$kpis" tiles />

    <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
        <div class="flex items-end gap-4 flex-wrap px-4 sm:px-6 pt-5 border-b border-line">
            <div class="flex flex-col gap-0.5 pb-3.5">
                <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $tab === 'all' ? 'Tous les bons de commande' : 'Bons de commande — '.mb_strtolower($tabLabels[$tab]) }}</h2>
                <div class="text-[12.5px] text-[#6C6658]">{{ $purchaseOrders->total() }} bon(s) · les plus récents en premier</div>
            </div>
            <x-tabs :items="$tabItems" :active="$tab" label="Étapes" class="sm:ml-auto max-w-full" />
        </div>

        <form method="GET" action="{{ route('purchase-orders.index') }}" class="flex items-center gap-2.5 flex-wrap px-4 sm:px-6 py-3.5 bg-paper/60 border-b border-line-soft">
            @if ($tab !== 'all') <input type="hidden" name="tab" value="{{ $tab }}"> @endif
            <label class="flex items-center gap-2 h-[40px] w-full sm:w-[320px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Numéro ou fournisseur…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13.5px] placeholder:text-ink-grey focus:ring-0">
            </label>
            @if (filled($search))
                <a href="{{ route('purchase-orders.index', array_filter(['tab' => $tab === 'all' ? null : $tab], 'filled')) }}" class="text-[12.5px] font-semibold text-[#6C6658] hover:text-navy">Effacer la recherche</a>
            @endif
        </form>

        @if ($purchaseOrders->isEmpty())
            <div class="px-5 py-12 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-ink-grey"><x-nav-icon name="truck" class="w-5 h-5" /></div>
                <div class="text-[14px] font-semibold">Aucun bon de commande</div>
                <div class="text-[12.5px] text-[#6C6658]">Changez d’onglet ou effacez la recherche.</div>
            </div>
        @else
            <div class="hidden md:grid grid-cols-[minmax(0,1fr)_130px_150px_160px_90px] gap-4 px-6 py-3 bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-[#6C6658]">
                <span>Commande</span><span>Date</span><span class="text-right">Montant</span><span>Statut</span><span></span>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($purchaseOrders as $po)
                    <li class="relative grid gap-1 md:grid-cols-[minmax(0,1fr)_130px_150px_160px_90px] md:gap-4 md:items-center px-4 sm:px-6 py-3.5 hover:bg-paper/60">
                        <div class="min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="font-mono text-[13px] font-semibold text-navy hover:underline after:absolute after:inset-0 md:after:hidden">{{ $po->number }}</a>
                                <x-purchase-order-status-badge :status="$po->status" class="md:hidden" />
                            </div>
                            <div class="text-[13px] text-[#4A4639] truncate">
                                {{ $po->supplier->name }}
                                @if ($po->workOrder) <span class="text-[#6C6658]">· pour {{ $po->workOrder->code() }}</span> @endif
                            </div>
                        </div>
                        {{-- Téléphone : date et montant sur une ligne ; bureau : une colonne chacun. --}}
                        <div class="flex items-baseline justify-between gap-2 md:contents">
                            <div class="text-[12.5px] text-[#6C6658] md:text-[13px] md:text-[#4A4639]">{{ $po->order_date->format('d/m/Y') }}</div>
                            <div class="font-mono text-[13.5px] text-navy md:text-right">{{ \App\Support\Money::format($po->total_amount) }}</div>
                        </div>
                        <div class="hidden md:block"><x-purchase-order-status-badge :status="$po->status" /></div>
                        <div class="hidden md:block text-right">
                            <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-sm btn-secondary">Ouvrir</a>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($purchaseOrders->hasPages())
                <div class="px-4 sm:px-6 py-3.5 border-t border-line bg-paper/60">{{ $purchaseOrders->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
