@php
    $search = request('search');
    $tabItems = [
        ['key' => 'active', 'label' => 'Actifs', 'count' => $counts['active'], 'href' => route('suppliers.index', array_filter(['search' => $search], 'filled'))],
        ['key' => 'inactive', 'label' => 'Désactivés', 'count' => $counts['inactive'], 'href' => route('suppliers.index', array_filter(['tab' => 'inactive', 'search' => $search], 'filled'))],
    ];
    $kpis = [
        ['label' => 'Fournisseurs actifs', 'value' => $stats['active'], 'sub' => 'à qui commander', 'dot' => 'bg-navy'],
        ['label' => 'Commandes en cours', 'value' => $stats['openOrders'], 'sub' => 'tous fournisseurs', 'dot' => 'bg-blue', 'href' => route('purchase-orders.index', ['tab' => 'open'])],
        ['label' => 'Achats '.now()->year, 'value' => \App\Support\Money::format($stats['yearSpend']), 'sub' => 'hors commandes annulées', 'dot' => 'bg-gold'],
    ];
@endphp

<x-app-layout :crumb="'Stock & achats'" :page-title="'Fournisseurs'">
    <x-slot:primaryAction>
        <a href="{{ route('suppliers.create') }}" data-modal class="btn btn-primary">+ Nouveau fournisseur</a>
    </x-slot:primaryAction>

    <x-kpi-band :items="$kpis" />

    <section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
        <div class="flex items-end gap-4 flex-wrap px-4 sm:px-6 pt-5 border-b border-line">
            <div class="flex flex-col gap-0.5 pb-3.5">
                <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $tab === 'active' ? 'Fournisseurs actifs' : 'Fournisseurs désactivés' }}</h2>
                <div class="text-[12.5px] text-ink-muted">{{ $suppliers->total() }} fournisseur(s) · par nom</div>
            </div>
            <x-tabs :items="$tabItems" :active="$tab" label="Filtres" class="sm:ml-auto max-w-full" />
        </div>

        <form method="GET" action="{{ route('suppliers.index') }}" class="flex items-center gap-2.5 flex-wrap px-4 sm:px-6 py-3.5 bg-paper/60 border-b border-line-soft">
            @if ($tab === 'inactive') <input type="hidden" name="tab" value="inactive"> @endif
            <label class="flex items-center gap-2 h-[40px] w-full sm:w-[320px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
                <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <span class="sr-only">Rechercher</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="Nom, contact ou e-mail…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13.5px] placeholder:text-ink-grey focus:ring-0">
            </label>
            @if (filled($search))
                <a href="{{ route('suppliers.index', array_filter(['tab' => $tab === 'inactive' ? 'inactive' : null], 'filled')) }}" class="text-[12.5px] font-semibold text-ink-muted hover:text-navy">Effacer la recherche</a>
            @endif
        </form>

        @if ($suppliers->isEmpty())
            <div class="px-5 py-12 flex flex-col items-center gap-2 text-center">
                <div class="w-[42px] h-[42px] rounded-full bg-line-soft flex items-center justify-center text-ink-grey"><x-nav-icon name="truck" class="w-5 h-5" /></div>
                <div class="text-[14px] font-semibold">Aucun fournisseur</div>
                <div class="text-[12.5px] text-ink-muted">Ajoutez la société à qui vous commandez vos pièces.</div>
            </div>
        @else
            <div class="hidden split:grid grid-cols-[minmax(0,1fr)_minmax(0,220px)_150px_170px_90px] gap-4 px-6 py-3 bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-muted">
                <span>Fournisseur</span><span>Contact</span><span class="text-right">Commandes</span><span class="text-right">Total acheté</span><span></span>
            </div>
            <ul class="m-0 p-0 list-none divide-y divide-line-soft">
                @foreach ($suppliers as $supplier)
                    <li class="relative grid gap-1.5 split:grid-cols-[minmax(0,1fr)_minmax(0,220px)_150px_170px_90px] split:gap-4 split:items-center px-4 sm:px-6 py-3.5 hover:bg-paper/60">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-10 h-10 rounded-[10px] bg-paper border border-line flex items-center justify-center text-gold flex-shrink-0"><x-nav-icon name="truck" class="w-5 h-5" /></span>
                            <span class="min-w-0">
                                <a href="{{ route('suppliers.show', $supplier) }}" class="block text-[14.5px] font-semibold text-navy truncate hover:underline after:absolute after:inset-0 split:after:hidden">{{ $supplier->name }}</a>
                                @if ($supplier->open_orders_count)
                                    <span class="text-[12px] text-blue font-medium">{{ $supplier->open_orders_count }} commande(s) en cours</span>
                                @endif
                            </span>
                        </div>
                        <div class="text-[13px] text-ink-body min-w-0 pl-[52px] split:pl-0">
                            <div class="truncate">{{ $supplier->contact_person ?? '—' }}</div>
                            <div class="text-[12px] text-ink-muted truncate">{{ collect([$supplier->phone, $supplier->email])->filter()->implode(' · ') ?: 'Pas de coordonnées' }}</div>
                        </div>
                        <div class="text-[13px] split:text-right pl-[52px] split:pl-0"><span class="font-mono">{{ $supplier->purchase_orders_count }}</span> <span class="split:hidden text-ink-muted">commande(s)</span></div>
                        <div class="font-mono text-[13.5px] text-navy split:text-right pl-[52px] split:pl-0">{{ \App\Support\Money::format($supplier->purchased_amount ?? 0) }}</div>
                        <div class="hidden split:block text-right">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-secondary">Ouvrir</a>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($suppliers->hasPages())
                <div class="px-4 sm:px-6 py-3.5 border-t border-line bg-paper/60">{{ $suppliers->links() }}</div>
            @endif
        @endif
    </section>
</x-app-layout>
