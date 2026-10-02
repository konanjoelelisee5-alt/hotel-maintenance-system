{{-- En-tête des tableaux de pilotage (admin, manager) : recherche d'OT et
     création rapide. Le contenu du slot (ex. sélecteur de période) s'insère entre les deux. --}}
<form method="GET" action="{{ route('work-orders.index') }}" class="flex-shrink-0">
    <label class="flex items-center gap-2 h-[38px] w-[240px] xl:w-[300px] px-3 rounded-[9px] border border-line bg-white text-[13px] focus-within:border-navy">
        <svg class="w-4 h-4 text-ink-grey flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
        <span class="sr-only">Rechercher</span>
        <input type="search" name="q" placeholder="Rechercher un ordre, une chambre…" class="flex-1 min-w-0 border-0 p-0 bg-transparent text-[13px] placeholder:text-ink-grey focus:ring-0">
    </label>
</form>

{{ $slot }}

@can('create', \App\Models\WorkOrder::class)
    <a href="{{ route('work-orders.create') }}" data-modal class="flex-shrink-0 inline-flex items-center h-[38px] px-4 rounded-[9px] bg-navy text-white text-[13px] font-semibold whitespace-nowrap hover:bg-navy-light">+ Nouvel ordre</a>
@endcan
