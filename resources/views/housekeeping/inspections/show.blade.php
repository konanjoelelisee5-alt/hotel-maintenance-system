{{-- Inspection d'une chambre. En cours : une zone par étape (x-wizard), chaque point
     enregistré dès qu'il est noté (fetch) ; un point non conforme reçoit un commentaire
     et une photo. Terminée : le résumé, avec les OT créés ou complétés.
     Données : RoomInspectionController::show(). --}}
@php
    $zones = $inspection->items->groupBy('zone');
    $hk = \App\Support\Housekeeping::class;
    $resultLabels = ['ok' => 'Conforme', 'nok' => 'Non conforme', 'na' => 'Sans objet'];
@endphp

{{-- En cours : comme le signalement, sans navigation sur téléphone et tablette (la barre
     d'avancement occupe le bas de l'écran). --}}
<x-app-layout crumb="Inspections" :page-title="'Inspection · '.$inspection->room->label" :back-route="route('inspections.index')" :focus="! $inspection->isDone()">
    @if (! $inspection->isDone())
        {{-- ===== En cours : une zone par étape (comme le signalement), puis remarques et fin.
             « Continuer » reste grisé tant qu'un point de la zone n'est pas noté. ===== --}}
        @php $steps = [...$zones->keys()->all(), 'Remarques et fin']; @endphp
        <form method="POST" action="{{ route('inspections.complete', $inspection) }}"
              x-data="roomInspection(@js([
                  'items' => $inspection->items->map(fn ($i) => ['id' => $i->id, 'zone' => $i->zone, 'category' => $i->category, 'result' => $i->result, 'comment' => $i->comment ?? '', 'hasPhoto' => (bool) $i->photo_path])->values(),
                  'zones' => $zones->keys()->values(),
                  'pointUrl' => route('inspections.points.update', [$inspection, '__ID__']),
                  'photoUrl' => route('inspections.points.photo', [$inspection, '__ID__']),
              ]))"
              class="grid gap-5 desk:grid-cols-[minmax(0,1fr)_320px] items-start pb-28 desk:pb-0">
            @csrf
            <div class="flex flex-col gap-5 min-w-0 w-full max-w-[760px]">
                <x-wizard.progress :steps="$steps" />

                @if ($previous)
                    <p x-show="step === 1" class="m-0 px-4 py-3 rounded-xl bg-info-bg text-[13px] text-blue">
                        Dernière inspection {{ $previous->completed_at->locale('fr')->diffForHumans() }} ({{ $previous->conformity() ?? '—' }} % conforme).
                    </p>
                @endif

                @foreach ($zones as $zone => $items)
                    <section x-show="step === {{ $loop->iteration }}" @if (! $loop->first) x-cloak @endif class="flex flex-col gap-4">
                        <div class="flex items-end gap-3">
                            <div class="flex-1 min-w-0">
                                <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">{{ $zone }}</h2>
                                <p class="m-0 mt-0.5 text-[13px] text-ink-muted">Notez chaque point : conforme, non conforme ou sans objet.</p>
                            </div>
                            <button type="button" @click="allOk(@js($zone))" class="btn btn-sm btn-secondary flex-shrink-0"><x-hk.icon name="check-check" :size="14" /> Tout conforme</button>
                        </div>
                        <div class="bg-white border border-line rounded-xl overflow-hidden">
                            @foreach ($items as $item)
                                <div class="px-5 py-3 border-b border-line-soft last:border-b-0 flex flex-col gap-2.5" x-data="{ id: {{ $item->id }} }">
                                    <div class="flex flex-col min-[480px]:flex-row min-[480px]:items-center gap-2.5">
                                        <div class="flex-1 min-w-0 flex items-center gap-2.5">
                                            <x-hk.icon :name="$hk::categoryIcon(\App\Enums\IssueCategory::from($item->category))" :size="16" class="text-gold" />
                                            <span class="text-[13.5px] font-medium text-navy">{{ $item->label }}</span>
                                            <span x-show="point(id).saving" class="text-[11px] text-ink-grey">…</span>
                                            <span x-show="point(id).error" x-cloak class="text-[11px] text-red">non enregistré, retouchez</span>
                                        </div>
                                        <div class="grid grid-cols-3 gap-1 p-[3px] rounded-[10px] bg-line-soft border border-line flex-shrink-0" role="radiogroup" aria-label="{{ $item->label }}">
                                            @foreach (['ok' => ['check', 'Conforme', 'bg-green text-white'], 'nok' => ['x', 'Non conforme', 'bg-red text-white'], 'na' => ['more', 'Sans objet', 'bg-white text-navy shadow-sm']] as $value => [$icon, $label, $on])
                                                <button type="button" role="radio" :aria-checked="point(id).result === '{{ $value }}'" @click="setResult(id, '{{ $value }}')"
                                                        class="h-9 px-2.5 rounded-[7px] text-[12px] font-semibold inline-flex items-center justify-center gap-1 whitespace-nowrap transition"
                                                        :class="point(id).result === '{{ $value }}' ? '{{ $on }}' : 'text-ink-grey hover:text-navy'">
                                                    <x-hk.icon :name="$icon" :size="13" /> {{ $value === 'na' ? 'S.O.' : $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    {{-- Non conforme : ce qui ne va pas, et une photo pour le technicien. --}}
                                    <div x-show="point(id).result === 'nok'" x-cloak class="flex flex-col min-[480px]:flex-row gap-2 pl-0 min-[480px]:pl-[26px]">
                                        <input type="text" maxlength="500" placeholder="Ce qui ne va pas (facultatif)" aria-label="Commentaire : {{ $item->label }}"
                                               x-model="point(id).comment" @change="save(id)"
                                               class="flex-1 min-w-0 h-10 px-3 rounded-[9px] border border-line text-[13.5px] focus:border-navy focus:ring-navy/20">
                                        <label class="btn btn-secondary cursor-pointer flex-shrink-0">
                                            <x-hk.icon name="camera" :size="15" />
                                            <span x-text="point(id).uploading ? 'Envoi…' : (point(id).hasPhoto ? 'Photo jointe' : 'Photo')"></span>
                                            <input type="file" accept="image/*" capture="environment" class="sr-only" @change="uploadPhoto(id, $event)">
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                {{-- Dernière étape : remarques, bilan, fin --}}
                <section x-show="step === {{ count($steps) }}" x-cloak class="flex flex-col gap-4">
                    <div>
                        <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Remarques et fin</h2>
                        <p class="m-0 mt-0.5 text-[13px] text-ink-muted" x-text="summary"></p>
                    </div>
                    <div class="bg-white border border-line rounded-xl px-5 py-4 ui-form">
                        <label for="notes">Remarques (facultatif)</label>
                        <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="Ex. : chambre à repeindre au prochain creux d'occupation."></textarea>
                    </div>
                </section>

                <x-wizard.actions :last="count($steps)" submit-label="Terminer l'inspection" submit-icon="check" submit-class="btn-primary" />

                <button type="submit" form="abandon-inspection" class="self-center desk:self-start text-[12.5px] font-semibold text-red hover:underline">Abandonner cette inspection</button>
            </div>

            {{-- Ordinateur : avancement de toute la chambre, zone par zone --}}
            <aside class="hidden desk:flex sticky top-[88px] flex-col gap-3 bg-white border border-line rounded-xl px-5 py-4">
                <div class="flex items-center justify-between text-[13px]">
                    <span class="font-semibold text-navy">Avancement</span>
                    <span class="font-mono text-ink-body"><span x-text="answered">0</span>/<span x-text="items.length">0</span></span>
                </div>
                <div class="h-1.5 rounded-full bg-line overflow-hidden"><div class="h-full bg-navy transition-all" :style="'width:' + (answered / items.length * 100) + '%'"></div></div>
                <ul class="m-0 p-0 list-none flex flex-col">
                    <template x-for="(zone, index) in zones" :key="zone">
                        <li class="flex items-center justify-between gap-2 py-2 border-b border-line-soft last:border-b-0 text-[13px]">
                            <span :class="step === index + 1 ? 'font-semibold text-navy' : 'text-ink-body'" x-text="zone"></span>
                            <span class="font-mono text-[12px]" :class="zoneDone(zone) ? 'text-green' : 'text-ink-grey'" x-text="zoneAnswered(zone) + '/' + zoneItems(zone).length"></span>
                        </li>
                    </template>
                </ul>
                <p class="m-0 text-[12.5px] text-ink-muted" x-text="summary"></p>
            </aside>
        </form>

        <form id="abandon-inspection" method="POST" action="{{ route('inspections.destroy', $inspection) }}" class="hidden"
              data-confirm="Les points déjà notés seront effacés. Rien n'a encore été envoyé à la maintenance." data-confirm-title="Abandonner l'inspection ?" data-confirm-label="Abandonner" data-confirm-tone="danger">
            @csrf
            @method('DELETE')
        </form>

        @push('scripts')
            <script>
                function roomInspection(config) {
                    const csrf = () => document.querySelector('meta[name=csrf-token]').content;
                    const shrink = (file) => window.hkMedia.shrink(file);

                    return {
                        items: config.items.map((i) => ({ ...i, saving: false, error: false, uploading: false })),
                        zones: config.zones,
                        step: 1,
                        busy: false,
                        btnState: '',
                        point(id) { return this.items.find((i) => i.id === id); },
                        get answered() { return this.items.filter((i) => i.result).length; },

                        // ----- Étapes : une zone par étape, puis remarques et fin -----
                        init() {
                            // Reprise d'une inspection commencée : on revient à la première zone incomplète.
                            const firstOpen = this.zones.findIndex((z) => !this.zoneDone(z));
                            this.step = firstOpen === -1 ? this.zones.length + 1 : firstOpen + 1;
                            history.replaceState({ step: this.step }, '', '#etape-' + this.step);
                            window.addEventListener('popstate', (e) => { this.step = Math.min(e.state?.step ?? 1, this.step); });
                        },
                        zoneItems(zone) { return this.items.filter((i) => i.zone === zone); },
                        zoneAnswered(zone) { return this.zoneItems(zone).filter((i) => i.result).length; },
                        zoneDone(zone) { return this.zoneAnswered(zone) === this.zoneItems(zone).length; },
                        get stepValid() { return this.step > this.zones.length ? this.canSend : this.zoneDone(this.zones[this.step - 1]); },
                        get canSend() { return this.answered === this.items.length && !this.busy; },
                        get hint() {
                            if (this.step > this.zones.length) return '';
                            const zone = this.zones[this.step - 1];
                            const left = this.zoneItems(zone).length - this.zoneAnswered(zone);
                            return left > 1 ? 'Encore ' + left + ' points à noter dans cette zone.' : 'Encore 1 point à noter dans cette zone.';
                        },
                        next() {
                            if (!this.stepValid || this.step > this.zones.length) return;
                            this.step++;
                            history.pushState({ step: this.step }, '', '#etape-' + this.step);
                            window.scrollTo({ top: 0 });
                        },
                        back() { history.back(); },
                        get summary() {
                            const nok = this.items.filter((i) => i.result === 'nok');
                            if (!nok.length) return 'Tout est conforme : aucun OT ne sera créé.';
                            const categories = new Set(nok.map((i) => i.category)).size;
                            return nok.length + ' point(s) non conforme(s) → ' + categories + ' OT (créé ou complété si la panne est déjà signalée).';
                        },

                        setResult(id, result) {
                            const p = this.point(id);
                            p.result = p.result === result ? null : result;
                            this.save(id);
                        },
                        allOk(zone) {
                            this.items.filter((i) => i.zone === zone && !i.result).forEach((i) => { i.result = 'ok'; this.save(i.id); });
                        },
                        async save(id) {
                            const p = this.point(id);
                            p.saving = true; p.error = false;
                            try {
                                const res = await fetch(config.pointUrl.replace('__ID__', id), {
                                    method: 'PATCH', credentials: 'same-origin',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                                    body: JSON.stringify({ result: p.result, comment: p.comment || null }),
                                });
                                p.error = !res.ok;
                            } catch (e) { p.error = true; }
                            p.saving = false;
                        },
                        async uploadPhoto(id, event) {
                            const file = event.target.files[0];
                            event.target.value = '';
                            if (!file) return;
                            const p = this.point(id);
                            p.uploading = true;
                            const data = new FormData();
                            data.append('_token', csrf());
                            data.append('photo', await shrink(file), 'photo.jpg');
                            try {
                                const res = await fetch(config.photoUrl.replace('__ID__', id), { method: 'POST', body: data, credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                                p.hasPhoto = res.ok; p.error = !res.ok;
                            } catch (e) { p.error = true; }
                            p.uploading = false;
                        },
                    };
                }
            </script>
        @endpush
    @else
        {{-- ===== Terminée : résumé ===== --}}
        @php
            $score = $inspection->conformity();
            $nok = $inspection->items->where('result', 'nok');
            $orders = $nok->pluck('workOrder')->filter()->unique('id');
        @endphp
        <div class="grid gap-5 split:grid-cols-[minmax(0,1fr)_320px] items-start">
            <div class="flex flex-col gap-5 min-w-0">
                <section class="bg-white border border-line rounded-xl px-5 py-5 flex items-center gap-4">
                    <span class="w-16 h-16 rounded-full flex items-center justify-center flex-shrink-0 font-mono text-[17px] font-semibold
                                 {{ $score === 100 ? 'bg-ok-bg text-green' : ($score >= 80 ? 'bg-warn-bg text-warn-ink' : 'bg-danger-bg text-red') }}">{{ $score ?? '—' }}%</span>
                    <div class="min-w-0">
                        <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $nok->isEmpty() ? 'Tout est conforme' : $nok->count().' point(s) non conforme(s)' }}</h2>
                        <p class="m-0 mt-0.5 text-[13px] text-ink-muted">{{ $inspection->completed_at->locale('fr')->translatedFormat('l j F Y à H\hi') }} · {{ $inspection->inspector->name }}</p>
                        <p class="m-0 mt-1 text-[12.5px] text-ink-grey">
                            {{ $inspection->items->where('result', 'ok')->count() }} conforme(s) · {{ $nok->count() }} non conforme(s) · {{ $inspection->items->where('result', 'na')->count() }} sans objet
                        </p>
                    </div>
                </section>

                @if ($nok->isNotEmpty())
                    <section class="bg-white border border-red/30 rounded-xl overflow-hidden">
                        <header class="px-5 py-3 border-b border-line-soft text-[14.5px] font-semibold text-navy">Non conformités</header>
                        @foreach ($nok as $item)
                            <div class="flex items-start gap-3 px-5 py-3 border-b border-line-soft last:border-b-0">
                                <x-hk.icon :name="$hk::categoryIcon(\App\Enums\IssueCategory::from($item->category))" :size="16" class="text-red mt-0.5" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-[13.5px] font-medium text-navy">{{ $item->label }} <span class="text-ink-grey font-normal">· {{ $item->zone }}</span></div>
                                    @if ($item->comment)<div class="text-[12.5px] text-ink-muted">{{ $item->comment }}</div>@endif
                                </div>
                                @if ($item->workOrder)
                                    <a href="{{ route('work-orders.show', $item->workOrder) }}" class="font-mono text-[12px] text-blue hover:underline whitespace-nowrap">{{ $item->workOrder->code() }}</a>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                <details class="bg-white border border-line rounded-xl overflow-hidden group">
                    <summary class="px-5 py-3.5 cursor-pointer list-none flex items-center justify-between text-[14px] font-semibold text-navy hover:bg-paper/60">
                        Tous les points ({{ $inspection->items->count() }})
                        <x-hk.icon name="chevron-down" :size="16" class="text-ink-grey transition-transform group-open:rotate-180" />
                    </summary>
                    @foreach ($zones as $zone => $items)
                        <div class="px-5 py-2 bg-paper/60 border-y border-line-soft text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">{{ $zone }}</div>
                        @foreach ($items as $item)
                            <div class="flex items-center justify-between gap-3 px-5 py-2.5 border-b border-line-soft last:border-b-0 text-[13px]">
                                <span>{{ $item->label }}</span>
                                <span class="font-semibold whitespace-nowrap {{ ['ok' => 'text-green', 'nok' => 'text-red', 'na' => 'text-ink-grey'][$item->result] ?? '' }}">{{ $resultLabels[$item->result] ?? '—' }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </details>
            </div>

            <aside class="flex flex-col gap-5 min-w-0">
                <section class="bg-white border border-line rounded-xl px-5 py-4">
                    <h2 class="m-0 mb-2 text-[14.5px] font-semibold text-navy">Envoyé à la maintenance</h2>
                    @forelse ($orders as $order)
                        <a href="{{ route('work-orders.show', $order) }}" class="flex items-center justify-between gap-2 py-2 border-b border-line-soft last:border-b-0 text-[13px] hover:underline">
                            <span class="truncate">{{ ($c = $hk::category($order)) ? $hk::categoryLabel($c) : $order->title }}</span>
                            <span class="font-mono text-[12px] text-blue">{{ $order->code() }}</span>
                        </a>
                    @empty
                        <p class="m-0 text-[13px] text-ink-grey">Rien : la chambre est conforme.</p>
                    @endforelse
                </section>
                @if ($inspection->notes)
                    <section class="bg-white border border-line rounded-xl px-5 py-4">
                        <h2 class="m-0 mb-1.5 text-[14.5px] font-semibold text-navy">Remarques</h2>
                        <p class="m-0 text-[13px] text-ink-body whitespace-pre-line">{{ $inspection->notes }}</p>
                    </section>
                @endif
                <form method="POST" action="{{ route('inspections.store') }}">
                    @csrf
                    <input type="hidden" name="room_number" value="{{ $inspection->room->number }}">
                    <button type="submit" class="btn btn-secondary w-full"><x-hk.icon name="rotate" :size="16" /> Refaire une inspection</button>
                </form>
            </aside>
        </div>
    @endif
</x-app-layout>
