{{-- Signalement des services hors Housekeeping (réception au comptoir, technicien) : le
     même parcours en 4 étapes que le HK (create.blade.php), une étape à la fois.
     ① lieu et client, ② problème, urgence et réclamation, ③ précisions (texte, voix,
     photo), ④ vérification et envoi. « Continuer » reste grisé tant que l'étape n'est pas
     complète : on ne peut rien oublier, et on n'a pas à faire défiler toute la page.
     Différences avec le HK : numéro tapé au clavier (pas de pavé), réclamation d'un client
     (réception), précisions écrites d'abord. Envoi en fetch pour joindre voix et photo. --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $user = auth()->user();
    $isReception = $user->role === \App\Enums\UserRole::Reception;
    $isTechnician = $user->role === \App\Enums\UserRole::Technicien;
    $steps = ['Lieu', 'Problème', 'Précisions', 'Vérifier et envoyer'];
    $categoryData = collect($categories)->map(fn ($c) => ['value' => $c->value, 'label' => $hk::categoryLabel($c)])->values();
    $occupancyData = collect($occupancies)->map(fn ($o) => ['value' => $o->value, 'label' => $o->label()])->values();
    // Carte d'option (lieu, client, catégorie) : sélection = bord marine + coche (comme le HK).
    $option = 'relative w-full rounded-xl border bg-white text-left flex items-center gap-3 px-3.5 py-3 transition hover:border-navy/40';
    $optionOn = "'border-navy ring-1 ring-navy bg-paper'";
    $tile = 'w-9 h-9 rounded-[9px] border flex items-center justify-center flex-shrink-0 transition';
    $panel = 'bg-white border border-line rounded-xl';
    $recapRows = [
        ['step' => 1, 'icon' => 'map-pin', 'label' => 'Lieu', 'value' => "placeLabel ? placeLabel + (!commonArea && occupancyLabel ? ' · ' + occupancyLabel : '') : null"],
        ['step' => 2, 'icon' => 'wrench', 'label' => 'Problème', 'value' => "categoryLabel ? [categoryLabel, urgent ? 'Urgence' : null, guestComplaint ? 'Réclamation client' : null].filter(Boolean).join(' · ') : null"],
        ['step' => 3, 'icon' => 'note', 'label' => 'Précisions', 'value' => "[note.trim() ? '« ' + note.trim().slice(0, 60) + (note.trim().length > 60 ? '…' : '') + ' »' : null, rec === 'done' ? 'message vocal' : null, photoName ? 'photo' : null].filter(Boolean).join(' · ') || (step > 3 ? 'Aucune' : null)"],
    ];
@endphp

<x-app-layout crumb="Signalement" page-title="Signaler une panne" focus :back-route="route($user->dashboardRoute())">
    <form x-data="deskReport(@js([
              'rooms' => $rooms, 'outOfService' => $outOfServiceRooms, 'commonAreas' => $commonAreas,
              'categories' => $categoryData, 'occupancies' => $occupancyData,
              'prefill' => $prefillRoom, 'openReports' => $openReports, 'maxSeconds' => $maxSeconds,
              'guestComplaint' => $isReception,
              'lookupUrl' => $isReception ? route('reception.dashboard') : null,
          ]))"
          @submit.prevent="send" method="POST" action="{{ route('quick-reports.store') }}"
          class="ui-form grid gap-5 desk:grid-cols-[minmax(0,1fr)_340px] items-start pb-28 desk:pb-0">
        @csrf
        <input type="hidden" name="room_number" :value="commonArea ? '' : roomNumber">
        <input type="hidden" name="category" :value="category ?? ''">
        <input type="hidden" name="common_area" :value="commonArea ? 1 : 0">
        <input type="hidden" name="common_area_id" :value="commonArea && commonAreaId ? commonAreaId : ''">
        <input type="hidden" name="urgent" :value="urgent ? 1 : 0">
        <input type="hidden" name="room_occupancy" :value="commonArea ? '' : (occupancy ?? '')">

        <div class="flex flex-col gap-5 min-w-0 w-full max-w-[760px]">
            <x-wizard.progress :steps="$steps" />

            {{-- ① Lieu et client --}}
            <section x-show="step === 1" class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Où se trouve la panne ?</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">Tapez le numéro de la chambre, ou choisissez un espace commun.</p>
                </div>

                <div class="grid grid-cols-2 gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line" role="radiogroup" aria-label="Type de lieu">
                    <button type="button" role="radio" :aria-checked="!commonArea" @click="commonArea = false"
                            class="h-10 rounded-[7px] text-[13.5px] flex items-center justify-center gap-2" :class="!commonArea ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey font-medium'">
                        <x-hk.icon name="bed" :size="17" /> Chambre
                    </button>
                    <button type="button" role="radio" :aria-checked="commonArea" @click="commonArea = true"
                            class="h-10 rounded-[7px] text-[13.5px] flex items-center justify-center gap-2" :class="commonArea ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey font-medium'">
                        <x-hk.icon name="building" :size="17" /> Espace commun
                    </button>
                </div>

                {{-- Chambre : numéro tapé au clavier, vérifié en direct, puis la situation du client --}}
                <div x-show="!commonArea" class="flex flex-col gap-3">
                    <div class="{{ $panel }} px-4 py-3.5 transition"
                         :class="{ '!border-green ring-1 ring-green/30': roomState === 'valid', '!border-red ring-1 ring-red/25': roomState === 'unknown', '!border-amber ring-1 ring-amber/25': roomState === 'out' }">
                        <label for="room_number_input" class="!m-0 text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Numéro de chambre</label>
                        <input id="room_number_input" type="text" inputmode="numeric" list="desk-rooms" autocomplete="off" maxlength="5"
                               x-model.trim="roomNumber" @input="occupancy = null" x-ref="roomInput"
                               :aria-invalid="roomState === 'unknown' || roomState === 'out'"
                               class="!mt-1 !h-auto !border-0 !shadow-none !ring-0 !p-0 !bg-transparent font-mono !text-[34px] font-medium leading-tight tracking-[.08em] text-navy placeholder:text-[#C9C3B6]"
                               placeholder="———">
                        <datalist id="desk-rooms"><template x-for="n in Object.keys(rooms)" :key="n"><option :value="n"></option></template></datalist>
                    </div>

                    <p class="m-0 min-h-[22px] text-[13px] font-medium" aria-live="polite">
                        <template x-if="roomState === 'valid'">
                            <span class="flex items-center gap-1.5 text-green"><x-hk.icon name="check-circle" :size="16" /> Chambre <span class="font-mono" x-text="roomNumber"></span><span x-show="roomFloor !== null && roomFloor !== ''" x-text="' · ' + (/^\d+$/.test(String(roomFloor)) ? 'étage ' + roomFloor : roomFloor)"></span></span>
                        </template>
                        <template x-if="roomState === 'out'">
                            <span class="flex items-center gap-1.5 text-amber"><x-hk.icon name="ban" :size="16" /> Chambre hors service : aucun signalement possible</span>
                        </template>
                        <template x-if="roomState === 'unknown'">
                            <span class="flex items-center gap-1.5 text-red"><x-hk.icon name="alert-circle" :size="16" /> Chambre inexistante</span>
                        </template>
                        <template x-if="roomState === 'typing' || roomState === ''">
                            <span class="text-ink-grey">Tapez le numéro de la chambre.</span>
                        </template>
                    </p>

                    <div x-show="roomState === 'valid'" class="flex flex-col gap-2.5">
                        <h3 class="m-0 text-[14px] font-semibold text-navy">Il y a un client ?</h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($occupancies as $o)
                                <button type="button" @click="occupancy = '{{ $o->value }}'" :aria-pressed="occupancy === '{{ $o->value }}'"
                                        class="{{ $option }} min-h-[56px]" :class="occupancy === '{{ $o->value }}' && {{ $optionOn }}">
                                    <span class="{{ $tile }}" :class="occupancy === '{{ $o->value }}' ? 'bg-navy border-navy text-white' : 'bg-paper border-line text-gold'">
                                        <x-hk.icon :name="$hk::occupancyIcon($o)" :size="17" />
                                    </span>
                                    <span class="text-[13.5px] font-medium leading-snug">{{ $o->label() }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Espaces communs déclarés dans « Lieux » (hors service exclus), + un choix libre. --}}
                <div x-show="commonArea" x-cloak class="grid grid-cols-1 min-[420px]:grid-cols-2 split:grid-cols-3 gap-2">
                    <template x-for="area in commonAreas" :key="area.id">
                        <button type="button" @click="commonAreaId = area.id" :aria-pressed="commonAreaId === area.id"
                                class="{{ $option }} min-h-[56px]" :class="commonAreaId === area.id && {{ $optionOn }}">
                            <span class="{{ $tile }}" :class="commonAreaId === area.id ? 'bg-navy border-navy text-white' : 'bg-paper border-line text-gold'"><x-hk.icon name="map-pin" :size="17" /></span>
                            <span class="text-[13.5px] font-medium" x-text="area.label"></span>
                        </button>
                    </template>
                    <button type="button" @click="commonAreaId = null" :aria-pressed="commonAreaId === null"
                            class="{{ $option }} min-h-[56px]" :class="commonAreaId === null && {{ $optionOn }}">
                        <span class="{{ $tile }}" :class="commonAreaId === null ? 'bg-navy border-navy text-white' : 'bg-paper border-line text-gold'"><x-hk.icon name="more" :size="17" /></span>
                        <span class="text-[13.5px] font-medium">Autre endroit</span>
                    </button>
                    <p x-show="commonAreaId === null" class="col-span-full m-0 text-[12.5px] text-ink-muted">Précisez l'endroit à l'étape 3.</p>
                </div>

                {{-- Déjà signalé ici : évite de signaler deux fois la même panne. --}}
                <div x-show="placeReports.length" x-cloak class="flex flex-col gap-2 px-4 py-3 rounded-xl border border-blue/20 bg-info-bg">
                    <p class="m-0 flex items-center gap-2 text-[13px] font-semibold text-blue">
                        <x-hk.icon name="info" :size="16" />
                        <span x-text="placeReports.length > 1 ? placeReports.length + ' signalements déjà en cours ici' : 'Un signalement déjà en cours ici'"></span>
                    </p>
                    <ul class="m-0 p-0 list-none flex flex-col gap-1">
                        <template x-for="r in placeReports" :key="r.code">
                            <li class="flex flex-wrap items-center gap-x-2 text-[13px] text-blue">
                                <span class="font-semibold" x-text="r.label"></span>
                                <span class="font-mono text-[12px]" x-text="r.code"></span>
                                <span class="text-[12px] opacity-80" x-text="'· ' + r.ago + (r.assigned ? ' · technicien affecté' : '')"></span>
                            </li>
                        </template>
                    </ul>
                    <a x-show="config.lookupUrl && !commonArea" :href="config.lookupUrl + '?chambre=' + encodeURIComponent(roomNumber)" class="self-start text-[13px] font-semibold text-blue underline">Voir où en est la réparation</a>
                </div>
            </section>

            {{-- ② Problème, urgence, réclamation --}}
            <section x-show="step === 2" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Quel est le problème ?</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">La maintenance précisera le diagnostic sur place.</p>
                </div>
                <div class="grid grid-cols-2 tab:grid-cols-3 split:grid-cols-4 gap-2">
                    @foreach ($categories as $c)
                        <button type="button" @click="category = '{{ $c->value }}'" :aria-pressed="category === '{{ $c->value }}'"
                                class="{{ $option }} !flex-col !items-start !gap-2.5 min-h-[92px] !py-3.5" :class="category === '{{ $c->value }}' && {{ $optionOn }}">
                            <span class="{{ $tile }}" :class="category === '{{ $c->value }}' ? 'bg-navy border-navy text-white' : 'bg-paper border-line text-gold'">
                                <x-hk.icon :name="$hk::categoryIcon($c)" :size="18" />
                            </span>
                            <span class="text-[13.5px] font-semibold text-navy">{{ $hk::categoryLabel($c) }}</span>
                            <span x-show="category === '{{ $c->value }}'" x-cloak class="absolute top-2.5 right-2.5 w-5 h-5 rounded-full bg-navy text-white flex items-center justify-center"><x-hk.icon name="check" :size="12" /></span>
                        </button>
                    @endforeach
                </div>

                {{-- Même panne déjà signalée au même endroit : on prévient, sans bloquer. --}}
                <div x-show="duplicate" x-cloak class="flex flex-col gap-3 px-4 py-3.5 rounded-xl border border-amber/40 bg-warn-bg">
                    <div class="flex items-start gap-2.5 text-warn-ink">
                        <x-hk.icon name="alert-triangle" :size="18" class="mt-0.5" />
                        <p class="m-0 text-[13px] leading-relaxed">
                            <strong>Déjà signalé :</strong> <span x-text="duplicate ? duplicate.label + ' · ' + duplicate.code + ', ' + duplicate.ago + (duplicate.assigned ? ' (technicien affecté)' : '') : ''"></span>.
                            La maintenance est déjà prévenue. S'il s'agit d'un autre problème, continuez.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a x-show="duplicate && duplicate.url" :href="duplicate ? duplicate.url : '#'" class="btn btn-sm btn-primary"><x-hk.icon name="plus" :size="14" /> Compléter ce signalement</a>
                        <a href="{{ route($user->dashboardRoute()) }}" class="btn btn-sm btn-secondary">C'est le même, ne rien envoyer</a>
                    </div>
                </div>

                <button type="button" role="switch" :aria-checked="urgent" @click="urgent = !urgent"
                        class="{{ $panel }} w-full flex items-center gap-3.5 px-4 py-3.5 text-left transition"
                        :class="urgent && '!border-red/40 bg-[#FDF3F2]'">
                    <span class="{{ $tile }}" :class="urgent ? 'bg-red border-red text-white' : 'bg-danger-soft border-red/20 text-red'"><x-hk.icon name="alert-triangle" :size="18" /></span>
                    <span class="flex flex-col flex-1 min-w-0">
                        <span class="text-[14.5px] font-semibold" :class="urgent ? 'text-red' : 'text-navy'">Urgence</span>
                        <span class="text-[12.5px] text-ink-muted">Eau qui coule, étincelles, odeur de gaz, client bloqué… L'astreinte est appelée.</span>
                    </span>
                    <span class="relative w-11 h-6 rounded-full flex-shrink-0 transition-colors" :class="urgent ? 'bg-red' : 'bg-[#D6D0C4]'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="urgent ? 'translate-x-5' : ''"></span>
                    </span>
                </button>

                {{-- Réclamation : services en contact avec le client, pas le technicien. --}}
                @unless ($isTechnician)
                    <button type="button" role="switch" :aria-checked="guestComplaint" @click="guestComplaint = !guestComplaint"
                            class="{{ $panel }} w-full flex items-center gap-3.5 px-4 py-3.5 text-left transition"
                            :class="guestComplaint && '!border-navy/40 bg-paper'">
                        <span class="{{ $tile }}" :class="guestComplaint ? 'bg-navy border-navy text-white' : 'bg-paper border-line text-gold'"><x-hk.icon name="user" :size="18" /></span>
                        <span class="flex flex-col flex-1 min-w-0">
                            <span class="text-[14.5px] font-semibold text-navy">Réclamation d'un client</span>
                            <span class="text-[12.5px] text-ink-muted">Le client attend une réponse : vous serez prévenu dès la réparation.</span>
                        </span>
                        <span class="relative w-11 h-6 rounded-full flex-shrink-0 transition-colors" :class="guestComplaint ? 'bg-navy' : 'bg-[#D6D0C4]'">
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="guestComplaint ? 'translate-x-5' : ''"></span>
                        </span>
                    </button>
                @endunless
            </section>

            {{-- ③ Précisions : texte, voix, photo (tout facultatif) --}}
            <section x-show="step === 3" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Précisions</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">Facultatif. Un mot, un message vocal ou une photo aident le technicien.</p>
                </div>

                <div class="{{ $panel }} p-4 flex flex-col gap-2">
                    <label for="note" class="!m-0 text-[13px] font-semibold text-navy">{{ $isReception ? 'Ce que dit le client' : 'Ce que vous avez constaté' }}</label>
                    <textarea id="note" name="note" rows="4" maxlength="1000" x-model="note"
                              placeholder="{{ $isReception ? 'Ex. : la climatisation fait du bruit et ne refroidit plus depuis ce matin.' : 'Ex. : fuite au raccord du chauffe-eau, sol mouillé.' }}"></textarea>
                </div>

                <div class="{{ $panel }} p-4 flex flex-col gap-3">
                    <div class="text-[13px] font-semibold text-navy">Message vocal et photo</div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <template x-if="micSupported">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <button type="button" x-show="rec !== 'done'" @click="toggleRecord" class="btn" :class="rec === 'on' ? 'btn-danger-solid' : 'btn-secondary'">
                                    <x-nav-icon name="mic" /> <span x-text="rec === 'on' ? 'Arrêter · ' + clock(seconds) : 'Message vocal'"></span>
                                </button>
                                <template x-if="rec === 'done'">
                                    <div class="flex items-center gap-2">
                                        <audio :src="audioUrl" controls class="h-10 max-w-[240px]"></audio>
                                        <button type="button" @click="resetAudio" class="btn btn-sm btn-ghost">Supprimer</button>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <label class="btn btn-secondary cursor-pointer !h-[40px] !border-line" x-show="!photoName">
                            <x-nav-icon name="camera" /> Photo
                            <input type="file" accept="image/*" class="sr-only" @change="pickPhoto">
                        </label>
                        <span x-show="photoName" x-cloak class="inline-flex items-center gap-2 text-[13px]"><x-hk.icon name="image" :size="16" class="text-gold" /> <span x-text="photoName"></span>
                            <button type="button" @click="resetPhoto" class="btn btn-sm btn-ghost">Retirer</button></span>
                    </div>
                    <p x-show="micError" x-cloak x-text="micError" class="m-0 px-3 py-2 rounded-lg bg-danger-bg text-danger-ink text-[13px]"></p>
                </div>
            </section>

            {{-- ④ Vérifier et envoyer --}}
            <section x-show="step === 4" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Vérifiez avant d'envoyer</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-ink-muted">
                        <span class="desk:hidden">Touchez « Modifier » pour corriger une étape.</span>
                        <span class="hidden desk:inline">Le récapitulatif est à droite : « Modifier » ramène à l'étape à corriger.</span>
                    </p>
                </div>
                <div class="desk:hidden">@include('quick-reports.partials.recap', ['rows' => $recapRows])</div>
                <p x-show="urgent" class="m-0 flex items-center gap-2 px-4 py-3 rounded-xl bg-danger-bg text-danger-ink text-[13px] font-medium">
                    <x-hk.icon name="alert-triangle" :size="16" /> Urgence : l'astreinte sera prévenue tout de suite.
                </p>
            </section>

            <p x-show="error" x-cloak x-text="error" class="m-0 px-4 py-3 rounded-lg bg-danger-bg text-danger-ink text-[13.5px] font-medium"></p>

            <x-wizard.actions :last="4" submit-label="Envoyer le signalement" />
        </div>

        {{-- Ordinateur : récapitulatif fixé à droite pendant tout le parcours --}}
        <aside class="hidden desk:block sticky top-[88px]">@include('quick-reports.partials.recap', ['rows' => $recapRows])</aside>
    </form>

    @push('scripts')
        <script>
            function deskReport(config) {
                // Enregistreur et fichiers hors de l'état Alpine (un Proxy les casse).
                let recorder = null, stream = null, chunks = [], timer = null, audioBlob = null, photoFile = null;
                const extensionFor = (type) => window.hkMedia.extensionFor(type);

                return {
                    config,
                    step: 1,
                    rooms: config.rooms,
                    outOfService: (config.outOfService || []).map(String),
                    openReports: config.openReports || [],
                    commonAreas: config.commonAreas,
                    categories: config.categories,
                    occupancies: config.occupancies,
                    roomNumber: config.prefill || '',
                    commonArea: false,
                    commonAreaId: null,
                    occupancy: null,
                    category: null,
                    note: '',
                    urgent: false,
                    guestComplaint: !!config.guestComplaint,
                    micSupported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder),
                    micError: '',
                    rec: 'idle', seconds: 0, audioUrl: null, photoName: null,
                    sending: false, btnState: '', error: '',

                    init() {
                        // Étape dans l'adresse : le retour du navigateur revient d'une étape.
                        history.replaceState({ step: 1 }, '', '#etape-1');
                        window.addEventListener('popstate', (e) => {
                            if (this.rec === 'on') this.stopRecord();
                            this.step = Math.min(e.state?.step ?? 1, this.step);
                        });
                        // Entrée valide l'étape (sauf dans la zone de texte, où elle va à la ligne).
                        this.$root.addEventListener('keydown', (e) => {
                            if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA') return;
                            e.preventDefault();
                            if (this.step < 4) this.next();
                        });
                        this.$nextTick(() => this.$refs.roomInput?.focus());
                    },

                    // ----- Lieu -----
                    get roomState() {
                        const n = this.roomNumber;
                        if (n === '') return '';
                        if (Object.prototype.hasOwnProperty.call(this.rooms, n)) return 'valid';
                        if (this.outOfService.includes(n)) return 'out';
                        const prefix = Object.keys(this.rooms).concat(this.outOfService).some((r) => r.startsWith(n));
                        return prefix ? 'typing' : 'unknown';
                    },
                    get roomFloor() { return this.roomState === 'valid' ? this.rooms[this.roomNumber] : null; },
                    get commonAreaLabel() { return this.commonAreas.find((a) => a.id === this.commonAreaId)?.label ?? 'Autre endroit'; },
                    get placeKey() {
                        if (this.commonArea) return this.commonAreaId ? 'area:' + this.commonAreaId : null;
                        return this.roomState === 'valid' ? this.roomNumber : null;
                    },
                    get placeReports() { return this.placeKey ? this.openReports.filter((r) => r.place === this.placeKey) : []; },
                    get duplicate() { return this.category ? this.placeReports.find((r) => r.category === this.category) ?? null : null; },
                    get placeLabel() { return this.commonArea ? this.commonAreaLabel : (this.roomState === 'valid' ? 'Chambre ' + this.roomNumber : null); },
                    get occupancyLabel() { return this.occupancies.find((o) => o.value === this.occupancy)?.label ?? null; },
                    get categoryLabel() { return this.categories.find((c) => c.value === this.category)?.label ?? null; },

                    // ----- Étapes -----
                    get placeOk() { return this.commonArea || (this.roomState === 'valid' && !!this.occupancy); },
                    get stepValid() {
                        return [this.placeOk, !!this.category, this.rec !== 'on', this.canSend][this.step - 1];
                    },
                    get hint() {
                        if (this.step === 1) {
                            if (this.commonArea) return '';
                            if (this.roomState !== 'valid') return 'Indiquez une chambre valide.';
                            return 'Dites s\'il y a un client.';
                        }
                        if (this.step === 2) return 'Choisissez le problème.';
                        if (this.step === 3) return 'Arrêtez l\'enregistrement pour continuer.';
                        return '';
                    },
                    get canSend() { return this.placeOk && !!this.category && !this.sending && this.rec !== 'on'; },
                    next() {
                        if (!this.stepValid || this.step >= 4) return;
                        this.error = '';
                        this.step++;
                        history.pushState({ step: this.step }, '', '#etape-' + this.step);
                        window.scrollTo({ top: 0 });
                    },
                    back() { history.back(); },
                    goTo(n) {
                        if (n >= this.step) return;
                        if (this.rec === 'on') this.stopRecord();
                        history.go(n - this.step);
                    },

                    // ----- Voix et photo -----
                    clock(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },
                    async toggleRecord() {
                        if (this.rec === 'on') return this.stopRecord();
                        this.micError = '';
                        try { stream = await navigator.mediaDevices.getUserMedia({ audio: true }); }
                        catch (e) { this.micError = 'Le micro est bloqué. Autorisez le micro pour ce site.'; return; }
                        const type = window.hkMedia.recorderType();
                        recorder = new MediaRecorder(stream, { ...(type ? { mimeType: type } : {}), audioBitsPerSecond: 32000 });
                        chunks = [];
                        recorder.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };
                        recorder.onstop = () => {
                            audioBlob = new Blob(chunks, { type: recorder.mimeType || type || 'audio/webm' });
                            this.audioUrl = URL.createObjectURL(audioBlob);
                            this.rec = 'done';
                            stream.getTracks().forEach((t) => t.stop());
                        };
                        recorder.start();
                        this.seconds = 0;
                        this.rec = 'on';
                        timer = setInterval(() => { if (++this.seconds >= config.maxSeconds) this.stopRecord(); }, 1000);
                    },
                    stopRecord() { clearInterval(timer); if (recorder && recorder.state !== 'inactive') recorder.stop(); },
                    resetAudio() { if (this.audioUrl) URL.revokeObjectURL(this.audioUrl); audioBlob = null; this.audioUrl = null; this.rec = 'idle'; this.seconds = 0; },
                    pickPhoto(e) { photoFile = e.target.files[0] || null; e.target.value = ''; this.photoName = photoFile?.name ?? null; },
                    resetPhoto() { photoFile = null; this.photoName = null; },

                    // ----- Envoi -----
                    async send() {
                        if (!this.canSend) return;
                        this.sending = true;
                        this.btnState = 'loading';
                        this.error = '';
                        const data = new FormData(this.$root);
                        if (this.guestComplaint) data.append('guest_complaint', '1');
                        if (audioBlob) data.append('audio', audioBlob, 'message-vocal.' + extensionFor(audioBlob.type));
                        if (photoFile) data.append('photo', photoFile, photoFile.name);
                        try {
                            const res = await fetch(this.$root.action, {
                                method: 'POST', body: data, credentials: 'same-origin',
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            if (res.ok) { this.btnState = 'success'; window.location.href = (await res.json()).redirect; return; }
                            if (res.status === 422) {
                                const json = await res.json();
                                this.error = Object.values(json.errors || {})[0]?.[0] || json.message;
                            } else if (res.status === 419) {
                                this.error = 'La page a expiré. Rechargez-la et recommencez.';
                            } else {
                                this.error = 'L\'envoi n\'a pas marché. Réessayez.';
                            }
                        } catch (e) {
                            this.error = 'Pas de réseau. Réessayez dans un instant.';
                        }
                        this.sending = false;
                        this.btnState = 'error';
                        setTimeout(() => { if (this.btnState === 'error') this.btnState = ''; }, 900);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
