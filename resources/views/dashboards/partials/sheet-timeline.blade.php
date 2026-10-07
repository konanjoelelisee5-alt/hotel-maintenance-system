{{-- Déroulé du jour (feuille) : passages planifiés aujourd'hui, en cartes qui défilent de côté.
     $timeline (DashboardPanels::timeline). Rien quand la journée est vide. --}}
@if ($timeline->isNotEmpty())
    <section aria-labelledby="timeline-title" class="pt-2">
        <h2 id="timeline-title" class="m-0 text-[20px] font-bold tracking-[-0.015em]">Déroulé du jour</h2>
        <div class="mt-3 flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
            @foreach ($timeline as $t)
                <a href="{{ route('work-orders.show', $t['id']) }}"
                   class="flex-shrink-0 w-[210px] flex flex-col gap-1.5 px-4 py-3.5 rounded-[20px] bg-paper hover:bg-line-soft transition-colors">
                    <span class="flex items-center gap-1.5 text-[13px] font-bold tabular"><x-nav-icon name="clock" class="w-4 h-4 text-blue" />{{ $t['time'] }}</span>
                    <span class="text-[14px] font-semibold leading-snug line-clamp-2">{{ $t['title'] }}</span>
                    <span class="text-[12.5px] text-ink-muted truncate">{{ $t['who'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
