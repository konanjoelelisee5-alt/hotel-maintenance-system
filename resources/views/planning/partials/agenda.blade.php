{{-- Agenda du planning, façon agenda de smartphone (logique : resources/js/agenda.js).
     $technicians : pastilles de filtre (planning général), ou null ;
     $technicianId : planning d'un seul technicien, ou null. --}}
@php
    $technicians ??= null;
    $technicianId ??= null;
@endphp

<div x-data="agenda({ eventsUrl: @js(route('planning.events')), technicianId: @js($technicianId ? (string) $technicianId : '') })"
     class="flex flex-col gap-4">

    {{-- ===== Barre d'outils : période, navigation, vues ===== --}}
    <section class="bg-white border border-line rounded-xl px-4 py-3.5 tab:px-5 flex flex-col gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2.5 min-w-0 mr-auto">
                <h2 class="m-0 text-[19px] font-semibold tracking-tight text-navy truncate" x-text="title"></h2>
                <span x-show="loading" x-cloak class="w-4 h-4 rounded-full border-2 border-line border-t-navy animate-spin" aria-label="Chargement"></span>
            </div>

            <div class="flex items-center gap-1.5">
                <button type="button" @click="go(-1)" class="w-9 h-9 rounded-[9px] border border-line flex items-center justify-center text-ink-body hover:bg-paper" aria-label="Période précédente">
                    <x-nav-icon name="back" class="w-4 h-4" />
                </button>
                <button type="button" @click="today()" class="h-9 px-3.5 rounded-[9px] border border-line text-[13px] font-semibold text-navy hover:bg-paper">Aujourd'hui</button>
                <button type="button" @click="go(1)" class="w-9 h-9 rounded-[9px] border border-line flex items-center justify-center text-ink-body hover:bg-paper" aria-label="Période suivante">
                    <x-nav-icon name="back" class="w-4 h-4 rotate-180" />
                </button>
            </div>

            {{-- Choix de la vue : contrôle segmenté, comme sur un téléphone. --}}
            <div class="flex items-center gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line w-full sm:w-auto" role="group" aria-label="Vue">
                @foreach (['day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois'] as $key => $label)
                    <button type="button" @click="setView('{{ $key }}')" :aria-pressed="view === '{{ $key }}'"
                            :class="view === '{{ $key }}' ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey hover:text-navy'"
                            class="flex-1 sm:flex-none h-[30px] px-3.5 rounded-[7px] text-[13px] transition">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        @if ($technicians)
            {{-- Filtre : une pastille par technicien, avec ses initiales. --}}
            <div class="flex gap-2 overflow-x-auto -mx-4 px-4 tab:mx-0 tab:px-0 pb-0.5" role="group" aria-label="Technicien">
                <button type="button" @click="filter('')" :aria-pressed="technicianId === ''"
                        :class="technicianId === '' ? 'bg-navy text-white border-navy' : 'bg-white text-ink-body border-line hover:bg-paper'"
                        class="flex-shrink-0 h-[34px] px-3.5 rounded-full border text-[13px] font-semibold transition">Toute l'équipe</button>
                @foreach ($technicians as $technician)
                    <button type="button" @click="filter('{{ $technician->id }}')" :aria-pressed="technicianId === '{{ $technician->id }}'"
                            :class="technicianId === '{{ $technician->id }}' ? 'bg-navy text-white border-navy' : 'bg-white text-ink-body border-line hover:bg-paper'"
                            class="flex-shrink-0 inline-flex items-center gap-2 h-[34px] pl-1 pr-3.5 rounded-full border text-[13px] font-medium transition">
                        <span class="w-[26px] h-[26px] rounded-full bg-gold text-navy text-[10.5px] font-bold flex items-center justify-center">{{ $technician->initialsOrGenerated() }}</span>
                        {{ $technician->name }}
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    <div x-show="failed" x-cloak class="flex items-center justify-between gap-3 px-4 py-3 rounded-[10px] bg-danger-soft border border-red/25 text-[13px] font-medium text-danger-ink">
        Impossible de charger le planning.
        <button type="button" @click="load()" class="underline font-semibold">Réessayer</button>
    </div>

    {{-- ===== Bande des 7 jours (Jour ; Semaine sur téléphone) — glisser pour changer de semaine ===== --}}
    <section x-show="view === 'day' || view === 'week'" :class="{ 'split:hidden': view === 'week' }"
             @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)"
             class="bg-white border border-line rounded-xl px-2 py-2.5 grid grid-cols-7 gap-1 select-none">
        <template x-for="day in weekDays" :key="day.getTime()">
            <button type="button" @click="pick(day)" class="flex flex-col items-center gap-1 py-1.5 rounded-[12px] transition"
                    :class="isSelected(day) ? 'bg-navy text-white' : 'hover:bg-paper'">
                <span class="text-[11px] font-semibold uppercase tracking-wide"
                      :class="isSelected(day) ? 'text-white/70' : 'text-ink-grey'" x-text="weekdayShort(day)"></span>
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-[15px] font-semibold"
                      :class="isToday(day) && !isSelected(day) ? 'text-gold ring-2 ring-gold/40' : ''" x-text="day.getDate()"></span>
                <span class="flex gap-0.5 h-1.5">
                    <template x-for="e in eventsOn(day).slice(0, 3)" :key="e.id">
                        <span class="w-1.5 h-1.5 rounded-full" :style="`background-color: ${isSelected(day) ? '#fff' : e.color}`"></span>
                    </template>
                </span>
            </button>
        </template>
    </section>

    {{-- ===== Vue Jour : programme du jour choisi ===== --}}
    <section x-show="view === 'day'" @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)" class="tab:max-w-3xl">
        @include('planning.partials.agenda-day', ['dayExpr' => 'selected'])
    </section>

    {{-- ===== Vue Semaine sur téléphone : la semaine jour par jour ===== --}}
    <section x-show="view === 'week'" class="split:hidden flex flex-col gap-5">
        <template x-for="day in weekDays" :key="day.getTime()">
            <div>
                <div class="flex items-baseline gap-2 mb-2 px-0.5">
                    <span class="text-[13px] font-semibold" :class="isToday(day) ? 'text-gold' : 'text-navy'"
                          x-text="dayLabel(day)"></span>
                    <span class="text-[12px] text-ink-grey" x-text="eventsOn(day).length ? eventsOn(day).length + ' intervention(s)' : ''"></span>
                </div>
                <template x-if="eventsOn(day).length === 0">
                    <div class="text-[12.5px] text-ink-grey px-3 py-2.5 rounded-[10px] border border-dashed border-line">Rien de prévu.</div>
                </template>
                <div class="flex flex-col gap-2">
                    <template x-for="e in eventsOn(day)" :key="e.id">
                        @include('planning.partials.agenda-card')
                    </template>
                </div>
            </div>
        </template>
    </section>

    {{-- ===== Vue Semaine sur ordinateur : grille horaire ===== --}}
    <section x-show="view === 'week'" class="hidden split:block bg-white border border-line rounded-xl overflow-hidden">
        <div class="grid grid-cols-[60px_repeat(7,minmax(0,1fr))] border-b border-line bg-paper/60">
            <div></div>
            <template x-for="day in weekDays" :key="day.getTime()">
                <button type="button" @click="pick(day); setView('day')" class="flex flex-col items-center gap-0.5 py-2.5 border-l border-line-soft hover:bg-paper">
                    <span class="text-[11px] font-semibold uppercase tracking-wide" :class="isToday(day) ? 'text-gold' : 'text-ink-grey'" x-text="weekdayShort(day)"></span>
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-[16px] font-semibold"
                          :class="isToday(day) ? 'bg-navy text-white' : 'text-navy'" x-text="day.getDate()"></span>
                </button>
            </template>
        </div>

        <div class="max-h-[68vh] overflow-y-auto">
            <div class="grid grid-cols-[60px_repeat(7,minmax(0,1fr))] relative">
                {{-- Heures --}}
                <div>
                    <template x-for="hour in hours" :key="hour">
                        <div class="relative text-right pr-2.5" :style="`height:${HOUR_PX}px`">
                            <span class="absolute right-2.5 font-mono text-[11px] text-ink-grey" :class="hour === hours[0] ? 'top-1' : '-top-2'" x-text="String(hour).padStart(2, '0') + ':00'"></span>
                        </div>
                    </template>
                </div>

                {{-- Une colonne par jour --}}
                <template x-for="day in weekDays" :key="day.getTime()">
                    <div class="relative border-l border-line-soft" :class="{ 'bg-gold/[.04]': isToday(day) }">
                        <template x-for="hour in hours" :key="hour">
                            <div class="border-t border-line-soft" :style="`height:${HOUR_PX}px`"></div>
                        </template>

                        <template x-for="e in layout(day)" :key="e.id">
                            <a :href="e.url" :style="e.style + cardStyle(e)" :title="`${time(e.startAt)} – ${e.title}`"
                               class="absolute overflow-hidden rounded-[8px] border px-2 py-1 text-[12px] leading-tight hover:shadow-md hover:z-10 transition"
                               :class="{ 'opacity-60': isPast(e) }">
                                <div class="font-mono text-[10.5px] text-ink-body" x-text="time(e.startAt)"></div>
                                <div class="font-semibold text-navy truncate" x-text="e.title"></div>
                                <div x-show="!e.compact" class="text-[11px] text-ink-muted truncate" x-text="[e.place, e.technician].filter(Boolean).join(' · ')"></div>
                            </a>
                        </template>

                        {{-- Ligne « maintenant » --}}
                        <template x-if="isToday(day) && nowTop !== null">
                            <div class="absolute left-0 right-0 z-20 pointer-events-none" :style="`top:${nowTop}px`">
                                <div class="relative h-[2px] bg-red">
                                    <span class="absolute -left-1 -top-[4px] w-2.5 h-2.5 rounded-full bg-red"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </section>

    {{-- ===== Vue Mois : grille + programme du jour choisi ===== --}}
    <div x-show="view === 'month'" class="grid gap-4 split:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)] items-start">
        <section @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)"
                 class="bg-white border border-line rounded-xl overflow-hidden select-none">
            <div class="grid grid-cols-7 border-b border-line bg-paper/60">
                <template x-for="day in weekDays" :key="day.getTime()">
                    <div class="py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-ink-grey" x-text="weekdayShort(day)"></div>
                </template>
            </div>
            <div class="grid grid-cols-7">
                <template x-for="day in monthDays" :key="day.getTime()">
                    <button type="button" @click="pick(day)"
                            class="min-h-[58px] tab:min-h-[92px] flex flex-col items-center tab:items-stretch gap-1 p-1.5 border-b border-r border-line-soft text-left transition hover:bg-paper"
                            :class="{ 'bg-paper/50': !isSameMonth(day) && !isSelected(day), 'bg-info-bg': isSelected(day) }">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-[13px] font-semibold tab:self-end"
                              :class="isToday(day) ? 'bg-navy text-white' : (isSameMonth(day) ? 'text-navy' : 'text-ink-grey/60')" x-text="day.getDate()"></span>
                        {{-- Téléphone : pastilles ; ordinateur : titres --}}
                        <span class="flex gap-0.5 tab:hidden">
                            <template x-for="e in eventsOn(day).slice(0, 3)" :key="e.id">
                                <span class="w-1.5 h-1.5 rounded-full" :style="`background-color:${e.color}`"></span>
                            </template>
                        </span>
                        <span class="hidden tab:flex flex-col gap-0.5 w-full">
                            <template x-for="e in eventsOn(day).slice(0, 2)" :key="e.id">
                                <span class="truncate rounded-[5px] border px-1.5 py-0.5 text-[11px] font-medium text-navy" :style="cardStyle(e)"
                                      x-text="time(e.startAt) + ' ' + e.title"></span>
                            </template>
                            <span x-show="eventsOn(day).length > 2" class="text-[11px] font-semibold text-ink-grey px-1.5" x-text="'+ ' + (eventsOn(day).length - 2) + ' autre(s)'"></span>
                        </span>
                    </button>
                </template>
            </div>
        </section>

        <section>
            @include('planning.partials.agenda-day', ['dayExpr' => 'selected'])
        </section>
    </div>
</div>
