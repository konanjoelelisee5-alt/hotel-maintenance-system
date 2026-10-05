{{-- Récapitulatif du signalement (état Alpine de quickReport), au gabarit des panneaux de
     l'admin : à l'étape 4 sur téléphone et tablette, fixé à droite sur ordinateur.
     « Modifier » ramène à l'étape concernée. --}}
<section class="bg-white border border-line rounded-xl overflow-hidden">
    <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
        <span class="w-8 h-8 rounded-[8px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0"><x-hk.icon name="clipboard" :size="16" /></span>
        <h3 class="m-0 flex-1 text-[14.5px] font-semibold text-navy">Récapitulatif</h3>
    </header>

    <dl class="m-0 px-5 py-1">
        @foreach ([
            ['step' => 1, 'icon' => 'map-pin', 'label' => 'Lieu', 'value' => "placeLabel ? placeLabel + (!commonArea && occupancyLabel ? ' · ' + occupancyLabel : '') : null"],
            ['step' => 2, 'icon' => 'wrench', 'label' => 'Problème', 'value' => "categoryLabel ? categoryLabel + (urgent ? ' · Urgence' : '') : null"],
            ['step' => 3, 'icon' => 'mic', 'label' => 'Message vocal', 'value' => "recState === 'recorded' ? 'Enregistré · ' + clock(seconds) : (step > 3 ? 'Aucun' : null)"],
            ['step' => 4, 'icon' => 'camera', 'label' => 'Photo et précisions', 'value' => "[photoUrl ? 'Photo jointe' : null, note.trim() ? '« ' + note.trim().slice(0, 60) + (note.trim().length > 60 ? '…' : '') + ' »' : null].filter(Boolean).join(' · ') || (step === 4 ? 'Aucune' : null)"],
        ] as $row)
            <div class="flex items-start gap-3 py-3 border-b border-line-soft last:border-b-0">
                <span class="mt-0.5 flex-shrink-0" :class="({{ $row['value'] }}) ? 'text-green' : 'text-[#C9C3B6]'">
                    <x-hk.icon :name="$row['icon']" :size="16" />
                </span>
                <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                    <dt class="text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">{{ $row['label'] }}</dt>
                    <dd class="m-0 text-[13.5px] break-words" :class="({{ $row['value'] }}) ? 'text-navy font-medium' : 'text-[#A09A8C]'"
                        x-text="({{ $row['value'] }}) || 'À compléter'"></dd>
                </span>
                <button type="button" x-show="step > {{ $row['step'] }}" @click="goTo({{ $row['step'] }})"
                        class="h-8 px-2 -mr-2 rounded-md text-[12.5px] font-semibold text-navy hover:bg-paper flex-shrink-0">Modifier</button>
            </div>
        @endforeach
    </dl>
</section>
