{{-- Inspection d'une chambre. En cours : la liste des points par zone, chaque point
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
        {{-- ===== En cours ===== --}}
        <form method="POST" action="{{ route('inspections.complete', $inspection) }}"
              x-data="roomInspection(@js([
                  'items' => $inspection->items->map(fn ($i) => ['id' => $i->id, 'zone' => $i->zone, 'category' => $i->category, 'result' => $i->result, 'comment' => $i->comment ?? '', 'hasPhoto' => (bool) $i->photo_path])->values(),
                  'pointUrl' => route('inspections.points.update', [$inspection, '__ID__']),
                  'photoUrl' => route('inspections.points.photo', [$inspection, '__ID__']),
              ]))"
              class="grid gap-5 split:grid-cols-[minmax(0,1fr)_320px] items-start pb-28 split:pb-0">
            @csrf
            <div class="flex flex-col gap-5 min-w-0">
                @if ($previous)
                    <p class="m-0 px-4 py-3 rounded-xl bg-[#EAF0F6] text-[13px] text-[#26496B]">
                        Dernière inspection {{ $previous->completed_at->locale('fr')->diffForHumans() }} ({{ $previous->conformity() ?? '—' }} % conforme).
                    </p>
                @endif

                @foreach ($zones as $zone => $items)
                    <section class="bg-white border border-line rounded-xl overflow-hidden">
                        <header class="flex items-center gap-3 px-5 py-3 border-b border-line-soft">
                            <h2 class="m-0 flex-1 text-[15px] font-semibold text-navy">{{ $zone }}</h2>
                            <button type="button" @click="allOk(@js($zone))" class="btn btn-sm btn-secondary"><x-hk.icon name="check-check" :size="14" /> Tout conforme</button>
                        </header>
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
                    </section>
                @endforeach

                <section class="bg-white border border-line rounded-xl px-5 py-4 ui-form">
                    <label for="notes">Remarques (facultatif)</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="1000" placeholder="Ex. : chambre à repeindre au prochain creux d'occupation."></textarea>
                </section>
            </div>

            {{-- Avancement et fin --}}
            <aside class="fixed split:sticky inset-x-0 bottom-0 split:top-[88px] z-30 bg-white border-t split:border border-line split:rounded-xl px-4 split:px-5 pt-3 split:py-4 pb-[calc(12px+env(safe-area-inset-bottom))] flex flex-col gap-3 shadow-[0_-8px_24px_-16px_rgba(14,33,54,.35)] split:shadow-none">
                <div class="flex items-center justify-between text-[13px]">
                    <span class="font-semibold text-navy">Avancement</span>
                    <span class="font-mono text-[#4A4639]"><span x-text="answered">0</span>/<span x-text="items.length">0</span></span>
                </div>
                <div class="h-1.5 rounded-full bg-line overflow-hidden"><div class="h-full bg-navy transition-all" :style="'width:' + (answered / items.length * 100) + '%'"></div></div>
                <p class="hidden split:block m-0 text-[12.5px] text-[#6C6658]" x-text="summary"></p>
                <div class="flex gap-2">
                    <button type="submit" :disabled="answered < items.length || busy" class="btn btn-primary flex-1">
                        <x-hk.icon name="check" :size="16" /> Terminer l'inspection
                    </button>
                </div>
                <p class="split:hidden m-0 -mt-1 text-[12px] text-ink-grey text-center" x-text="answered < items.length ? 'Notez tous les points pour terminer.' : summary"></p>
                <button type="submit" form="abandon-inspection" class="hidden split:block text-[12.5px] font-semibold text-red hover:underline">Abandonner cette inspection</button>
            </aside>
        </form>

        <form id="abandon-inspection" method="POST" action="{{ route('inspections.destroy', $inspection) }}" class="split:hidden text-center"
              data-confirm="Les points déjà notés seront effacés. Rien n'a encore été envoyé à la maintenance." data-confirm-title="Abandonner l'inspection ?" data-confirm-label="Abandonner" data-confirm-tone="danger">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-[12.5px] font-semibold text-red hover:underline">Abandonner cette inspection</button>
        </form>

        @push('scripts')
            <script>
                function roomInspection(config) {
                    const csrf = () => document.querySelector('meta[name=csrf-token]').content;
                    const shrink = (file, max = 1600) => new Promise((resolve) => {
                        const url = URL.createObjectURL(file), img = new Image();
                        img.onload = () => {
                            const scale = Math.min(1, max / Math.max(img.width, img.height));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(img.width * scale);
                            canvas.height = Math.round(img.height * scale);
                            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                            URL.revokeObjectURL(url);
                            canvas.toBlob((blob) => resolve(blob || file), 'image/jpeg', 0.8);
                        };
                        img.onerror = () => { URL.revokeObjectURL(url); resolve(file); };
                        img.src = url;
                    });

                    return {
                        items: config.items.map((i) => ({ ...i, saving: false, error: false, uploading: false })),
                        busy: false,
                        point(id) { return this.items.find((i) => i.id === id); },
                        get answered() { return this.items.filter((i) => i.result).length; },
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
                                 {{ $score === 100 ? 'bg-[#E6F3EC] text-green' : ($score >= 80 ? 'bg-[#FBF1DF] text-[#7A5A16]' : 'bg-[#FDECEA] text-red') }}">{{ $score ?? '—' }}%</span>
                    <div class="min-w-0">
                        <h2 class="m-0 text-[17px] font-semibold text-navy">{{ $nok->isEmpty() ? 'Tout est conforme' : $nok->count().' point(s) non conforme(s)' }}</h2>
                        <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">{{ $inspection->completed_at->locale('fr')->translatedFormat('l j F Y à H\hi') }} · {{ $inspection->inspector->name }}</p>
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
                                    @if ($item->comment)<div class="text-[12.5px] text-[#6C6658]">{{ $item->comment }}</div>@endif
                                </div>
                                @if ($item->workOrder)
                                    <a href="{{ route('work-orders.show', $item->workOrder) }}" class="font-mono text-[12px] text-[#26496B] hover:underline whitespace-nowrap">{{ $item->workOrder->code() }}</a>
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
                            <span class="font-mono text-[12px] text-[#26496B]">{{ $order->code() }}</span>
                        </a>
                    @empty
                        <p class="m-0 text-[13px] text-ink-grey">Rien : la chambre est conforme.</p>
                    @endforelse
                </section>
                @if ($inspection->notes)
                    <section class="bg-white border border-line rounded-xl px-5 py-4">
                        <h2 class="m-0 mb-1.5 text-[14.5px] font-semibold text-navy">Remarques</h2>
                        <p class="m-0 text-[13px] text-[#4A4639] whitespace-pre-line">{{ $inspection->notes }}</p>
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
