{{-- Admin et manager : retrouver un ordre (la barre de recherche de la maquette) et en créer un. --}}
<div class="flex flex-col sm:flex-row gap-2.5">
    <form method="GET" action="{{ route('work-orders.index') }}" role="search" class="relative flex-1 min-w-0">
        <label for="q" class="sr-only">Rechercher un ordre</label>
        <x-nav-icon name="search" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-ink-grey pointer-events-none" />
        <input id="q" name="q" type="search" autocomplete="off" placeholder="Rechercher un ordre, une chambre…"
               class="w-full h-14 pl-14 pr-5 rounded-full border-0 bg-paper text-[16px] font-semibold placeholder:font-medium placeholder:text-[15px] placeholder:text-ink-grey
                      focus:bg-white focus:ring-2 focus:ring-blue/40 transition">
    </form>
    @can('create', \App\Models\WorkOrder::class)
        <a href="{{ route('work-orders.create') }}" data-modal class="btn btn-primary btn-lg !h-14 !rounded-full !px-7">
            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Nouvel ordre
        </a>
    @endcan
</div>
