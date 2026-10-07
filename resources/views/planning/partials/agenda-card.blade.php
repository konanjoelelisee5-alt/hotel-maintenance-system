{{-- Carte d'une intervention dans les listes de l'agenda (variable Alpine « e »).
     Colonne horaire à gauche, carte teintée de la couleur de priorité à droite. --}}
<a :href="e.url" class="flex gap-3 group" :class="{ 'opacity-60': isPast(e) && !isNow(e) }">
    <div class="w-12 flex-shrink-0 pt-2.5 text-right">
        <div class="font-mono text-[13px] font-semibold text-navy" x-text="time(e.startAt)"></div>
        <div class="font-mono text-[11px] text-ink-grey" x-text="time(e.endAt)"></div>
    </div>
    <div class="flex-1 min-w-0 rounded-[12px] border px-3.5 py-3 transition group-hover:shadow-md"
         :style="cardStyle(e)" :class="{ 'ring-2 ring-red/40': isNow(e) }">
        <div class="flex items-center gap-2 flex-wrap mb-1">
            <span class="font-mono text-[11px] text-ink-muted" x-text="e.code"></span>
            <span x-show="isNow(e)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-red text-white text-[10.5px] font-bold uppercase tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> En cours
            </span>
            <span x-show="e.urgent" class="px-1.5 py-0.5 rounded-md bg-danger-soft text-red text-[10.5px] font-bold uppercase tracking-wide">Urgent</span>
            <span class="ml-auto px-2 py-0.5 rounded-full bg-white/80 border border-line text-[11px] font-medium text-ink-body" x-text="e.status_label"></span>
        </div>
        <div class="text-[14.5px] font-semibold text-navy leading-snug" x-text="e.title"></div>
        <div class="flex items-center gap-x-3 gap-y-1 flex-wrap mt-2 text-[12.5px] text-ink-muted">
            <span x-show="e.place" class="inline-flex items-center gap-1">
                <x-nav-icon name="building" class="w-3.5 h-3.5" /><span x-text="e.place"></span>
            </span>
            <span x-show="e.technician" class="inline-flex items-center gap-1.5">
                <span class="w-5 h-5 rounded-full bg-gold text-navy text-[9px] font-bold flex items-center justify-center" x-text="e.initials"></span>
                <span x-text="e.technician"></span>
            </span>
            <span x-show="!e.technician" class="text-amber font-medium">Non affecté</span>
            <span class="inline-flex items-center gap-1 ml-auto">
                <x-nav-icon name="status" class="w-3.5 h-3.5" /><span x-text="duration(e)"></span>
            </span>
        </div>
    </div>
</a>
