<x-app-layout crumb="Signalement rapide" page-title="Signaler un problème" :back-route="route(auth()->user()->dashboardRoute())">
    {{-- Pensé pour le personnel d'étage : pictogrammes, gros boutons, message vocal
         plutôt que texte. Envoi en fetch pour joindre l'enregistrement (Blob) au formulaire. --}}
    <form x-data="quickReport(@js(['rooms' => $rooms, 'commonAreas' => $commonAreas, 'prefill' => $prefillRoom, 'maxSeconds' => $maxSeconds]))"
          @submit.prevent="send" method="POST" action="{{ route('quick-reports.store') }}"
          class="max-w-xl w-full mx-auto flex flex-col gap-4 pb-28 lg:pb-0">
        @csrf
        <input type="hidden" name="category" :value="category ?? ''">
        <input type="hidden" name="common_area" :value="commonArea ? 1 : 0">
        <input type="hidden" name="common_area_id" :value="commonArea && commonAreaId ? commonAreaId : ''">
        <input type="hidden" name="urgent" :value="urgent ? 1 : 0">
        <input type="hidden" name="room_occupancy" :value="commonArea ? '' : (occupancy ?? '')">

        {{-- 1. Où ? --}}
        <section class="bg-white border border-line rounded-xl p-4 flex flex-col gap-3">
            <h2 class="flex items-center gap-2 text-[15px] font-semibold"><span class="text-2xl">📍</span> Où ?</h2>
            <div class="flex items-stretch gap-2">
                <input type="text" name="room_number" x-model.trim="roomNumber" @input="commonArea = false"
                       inputmode="numeric" autocomplete="off" placeholder="N° chambre" aria-label="Numéro de chambre"
                       class="flex-1 min-w-0 h-16 text-center text-3xl font-semibold tracking-widest rounded-lg border-2"
                       :class="roomKnown ? 'border-green bg-[#E6F3EC]' : (roomNumber && !commonArea ? 'border-red bg-[#FDECEA]' : 'border-line')">
                <button type="button" @click="commonArea = !commonArea; if (commonArea) roomNumber = ''"
                        class="w-28 flex-shrink-0 rounded-lg border-2 text-[12px] font-semibold leading-tight px-2"
                        :class="commonArea ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line text-ink-grey'">
                    <span class="block text-2xl">🏢</span>Hall, piscine…
                </button>
            </div>
            {{-- Espaces communs déclarés dans « Lieux » (hors service exclus), + un choix libre. --}}
            <div x-show="commonArea" class="grid grid-cols-2 gap-2">
                <template x-for="area in commonAreas" :key="area.id">
                    <button type="button" @click="commonAreaId = area.id" x-text="area.label"
                            class="h-14 rounded-lg border-2 text-[14px] font-semibold px-2"
                            :class="commonAreaId === area.id ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line'"></button>
                </template>
                <button type="button" @click="commonAreaId = null"
                        class="h-14 rounded-lg border-2 text-[13px] font-semibold px-2"
                        :class="commonAreaId === null ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line text-ink-grey'">❓ Autre endroit</button>
            </div>
            <p class="text-[13px] font-medium min-h-[1.25rem]">
                <span x-show="roomKnown" class="text-green">✓ Chambre <span x-text="roomNumber"></span><span x-show="roomFloor" x-text="' · étage ' + roomFloor"></span></span>
                <span x-show="roomNumber && !roomKnown && !commonArea" class="text-red">✗ Chambre inconnue</span>
                <span x-show="commonArea && commonAreaLabel" class="text-green">✓ <span x-text="commonAreaLabel"></span></span>
                <span x-show="commonArea && !commonAreaLabel" class="text-navy">✓ Autre endroit : dites où dans le message 🎤</span>
            </p>
            {{-- Occupation : l'application n'est pas reliée à Opera, c'est l'agent qui sait.
                 Client sorti → réparation avant son retour ; chambre libre → blocage possible. --}}
            <div x-show="roomKnown && !commonArea" class="flex flex-col gap-2">
                <p class="text-[13px] font-semibold">Il y a un client ?</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($occupancies as $o)
                        <button type="button" @click="occupancy = '{{ $o->value }}'"
                                class="h-16 rounded-lg border-2 flex items-center justify-center gap-2 px-2 text-[13px] font-semibold leading-tight"
                                :class="occupancy === '{{ $o->value }}' ? 'border-navy bg-[#EAF0F6] text-navy' : 'border-line'">
                            <span class="text-2xl">{{ $o->emoji() }}</span> {{ $o->label() }}
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 2. Quoi ? --}}
        <section class="bg-white border border-line rounded-xl p-4 flex flex-col gap-3">
            <h2 class="flex items-center gap-2 text-[15px] font-semibold"><span class="text-2xl">🔧</span> Quel problème ?</h2>
            <div class="grid grid-cols-3 gap-2">
                @foreach ($categories as $c)
                    <button type="button" @click="category = '{{ $c->value }}'"
                            class="h-24 rounded-xl border-2 flex flex-col items-center justify-center gap-1 px-1"
                            :class="category === '{{ $c->value }}' ? 'border-navy bg-[#EAF0F6] ring-2 ring-navy/20' : 'border-line bg-white'">
                        <span class="text-4xl leading-none">{{ $c->emoji() }}</span>
                        <span class="text-[11.5px] font-medium text-center leading-tight">{{ $c->label() }}</span>
                    </button>
                @endforeach
            </div>
            <button type="button" @click="urgent = !urgent"
                    class="h-16 rounded-xl border-2 flex items-center justify-center gap-3 font-bold text-[15px]"
                    :class="urgent ? 'border-red bg-red text-white' : 'border-[#F0C9C5] bg-[#FDECEA] text-red'">
                <span class="text-3xl">🚨</span>
                <span x-text="urgent ? 'URGENT ✓' : 'C\'est URGENT ?'"></span>
            </button>
            <p class="text-[11.5px] text-ink-grey -mt-1 text-center">Eau qui coule, étincelles, odeur de gaz, client bloqué…</p>
        </section>

        {{-- 3. Le mégaphone --}}
        <section class="bg-white border border-line rounded-xl p-4 flex flex-col items-center gap-3">
            <h2 class="self-start flex items-center gap-2 text-[15px] font-semibold"><span class="text-2xl">🎤</span> Expliquez avec votre voix</h2>

            <template x-if="micSupported">
                <div class="flex flex-col items-center gap-3 w-full">
                    <button type="button" @click="toggleRecord" x-show="recState !== 'recorded'"
                            class="relative w-32 h-32 rounded-full flex items-center justify-center text-6xl shadow-lg"
                            :class="recState === 'recording' ? 'bg-red text-white' : 'bg-gold text-navy'"
                            :aria-label="recState === 'recording' ? 'Arrêter' : 'Parler'">
                        <span x-show="recState === 'recording'" class="absolute inset-0 rounded-full bg-red animate-ping opacity-40"></span>
                        <span class="relative" x-text="recState === 'recording' ? '⏹️' : '🎤'"></span>
                    </button>
                    <p x-show="recState === 'idle'" class="text-[13px] text-ink-grey text-center">Touchez le micro et parlez.<br>Touchez encore pour arrêter.</p>
                    <p x-show="recState === 'recording'" class="text-[22px] font-semibold text-red tabular-nums">
                        ● <span x-text="clock(seconds)"></span> <span class="text-[13px] text-ink-grey font-normal">/ <span x-text="clock(maxSeconds)"></span></span>
                    </p>

                    <div x-show="recState === 'recorded'" class="w-full flex flex-col items-center gap-3">
                        <p class="text-green font-semibold text-[15px]">✓ Message enregistré (<span x-text="clock(seconds)"></span>)</p>
                        <audio :src="audioUrl" controls class="w-full"></audio>
                        <button type="button" @click="resetAudio" class="h-12 px-5 rounded-lg border-2 border-line text-[14px] font-semibold">🔄 Recommencer</button>
                    </div>
                </div>
            </template>

            <p x-show="!micSupported" class="w-full px-3 py-2.5 rounded-lg bg-[#FBF1DF] text-[#7A5A16] text-[13px]">
                Le micro n'est pas disponible ici (connexion non sécurisée ou navigateur trop ancien). Prenez une photo ou écrivez ci-dessous.
            </p>
            <p x-show="micError" x-text="micError" class="w-full px-3 py-2.5 rounded-lg bg-[#FDECEA] text-[#8A1F16] text-[13px]"></p>
        </section>

        {{-- 4. Photo + texte facultatifs --}}
        <section class="bg-white border border-line rounded-xl p-4 flex flex-col gap-3">
            <h2 class="flex items-center gap-2 text-[15px] font-semibold"><span class="text-2xl">📷</span> Photo <span class="text-[12px] text-ink-grey font-normal">(si possible)</span></h2>
            <label x-show="!photoUrl" class="h-20 rounded-xl border-2 border-dashed border-line flex items-center justify-center gap-2 text-[14px] font-semibold text-navy cursor-pointer">
                <span class="text-3xl">📷</span> Prendre une photo
                <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pickPhoto">
            </label>
            <div x-show="photoUrl" class="flex items-center gap-3">
                <img :src="photoUrl" alt="" class="w-24 h-24 object-cover rounded-lg border border-line">
                <button type="button" @click="resetPhoto" class="h-12 px-4 rounded-lg border-2 border-line text-[14px] font-semibold">🗑️ Retirer</button>
            </div>

            <button type="button" x-show="!showNote" @click="showNote = true" class="self-start text-[13px] text-ink-grey underline">✏️ Écrire un message (facultatif)</button>
            <textarea x-show="showNote" name="note" rows="3" maxlength="1000" placeholder="Votre message…"
                      class="w-full rounded-lg border-line text-[14px]"></textarea>
        </section>

        <p x-show="error" x-text="error" class="px-4 py-3 rounded-lg bg-[#FDECEA] text-[#8A1F16] text-[14px] font-medium"></p>

        {{-- Envoi : collé au-dessus de la barre de navigation mobile. --}}
        <div class="fixed lg:static bottom-[64px] inset-x-0 z-30 px-4 lg:px-0 py-3 lg:py-0 bg-white/95 backdrop-blur lg:bg-transparent border-t border-line lg:border-0">
            <button type="submit" :disabled="!canSend"
                    class="max-w-xl mx-auto w-full h-16 rounded-xl flex items-center justify-center gap-3 text-[17px] font-bold text-white disabled:opacity-40"
                    :class="urgent ? 'bg-red' : 'bg-green'">
                <span x-show="!sending" class="text-2xl">📨</span>
                <span x-show="sending" class="w-6 h-6 rounded-full border-[3px] border-white/40 border-t-white animate-spin"></span>
                <span x-text="sending ? 'Envoi…' : 'Envoyer'"></span>
            </button>
            <p x-show="!canSend && !sending" class="max-w-xl mx-auto mt-1.5 text-center text-[12px] text-ink-grey">
                <span x-show="!commonArea && !roomKnown">📍 Chambre ?</span>
                <span x-show="!commonArea && roomKnown && !occupancy">🛏️ Client ?</span>
                <span x-show="!category">🔧 Problème ?</span>
                <span x-show="recState === 'recording'">⏹️ Arrêtez l'enregistrement</span>
            </p>
        </div>
    </form>

    @push('scripts')
        <script>
            function quickReport(config) {
                // Objets navigateur gardés hors de l'état Alpine : un MediaRecorder ou un
                // Blob enveloppé dans un Proxy réactif ne fonctionne plus.
                let recorder = null, stream = null, chunks = [], timer = null;
                let audioBlob = null, photoBlob = null;

                const extensionFor = (type) => type.includes('mp4') ? 'm4a' : (type.includes('ogg') ? 'ogg' : 'webm');

                const beep = () => {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator(), gain = ctx.createGain();
                        osc.frequency.value = 880;
                        gain.gain.value = 0.15;
                        osc.connect(gain); gain.connect(ctx.destination);
                        osc.start(); osc.stop(ctx.currentTime + 0.2);
                    } catch (e) {}
                };

                // Photo réduite sur le téléphone (1600 px, JPEG) : envoi rapide en 3G/wifi faible.
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
                    rooms: config.rooms,
                    roomNumber: config.prefill || '',
                    commonArea: false,
                    commonAreas: config.commonAreas,
                    commonAreaId: null,
                    category: null,
                    occupancy: null,
                    urgent: false,
                    showNote: false,
                    micSupported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder),
                    micError: '',
                    recState: 'idle',
                    seconds: 0,
                    maxSeconds: config.maxSeconds,
                    audioUrl: null,
                    photoUrl: null,
                    sending: false,
                    error: '',

                    get roomKnown() { return this.roomNumber !== '' && Object.prototype.hasOwnProperty.call(this.rooms, this.roomNumber); },
                    get roomFloor() { return this.roomKnown ? this.rooms[this.roomNumber] : null; },
                    get commonAreaLabel() { return this.commonAreas.find((a) => a.id === this.commonAreaId)?.label ?? null; },
                    get placeOk() { return this.commonArea || (this.roomKnown && !!this.occupancy); },
                    get canSend() { return this.placeOk && !!this.category && !this.sending && this.recState !== 'recording'; },

                    clock(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },

                    async toggleRecord() {
                        if (this.recState === 'recording') return this.stopRecord();
                        this.micError = '';
                        try {
                            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        } catch (e) {
                            this.micError = 'Le micro est bloqué. Autorisez le micro pour ce site dans le navigateur.';
                            return;
                        }
                        const type = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus']
                            .find((t) => MediaRecorder.isTypeSupported(t));
                        recorder = new MediaRecorder(stream, { ...(type ? { mimeType: type } : {}), audioBitsPerSecond: 32000 });
                        chunks = [];
                        recorder.ondataavailable = (e) => { if (e.data.size) chunks.push(e.data); };
                        recorder.onstop = () => {
                            audioBlob = new Blob(chunks, { type: recorder.mimeType || type || 'audio/webm' });
                            this.audioUrl = URL.createObjectURL(audioBlob);
                            this.recState = 'recorded';
                            stream.getTracks().forEach((t) => t.stop());
                        };
                        recorder.start();
                        this.seconds = 0;
                        this.recState = 'recording';
                        navigator.vibrate?.(60);
                        timer = setInterval(() => { if (++this.seconds >= this.maxSeconds) this.stopRecord(); }, 1000);
                    },

                    stopRecord() {
                        clearInterval(timer);
                        if (recorder && recorder.state !== 'inactive') recorder.stop();
                        navigator.vibrate?.(60);
                    },

                    resetAudio() {
                        if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                        audioBlob = null;
                        this.audioUrl = null;
                        this.seconds = 0;
                        this.recState = 'idle';
                    },

                    async pickPhoto(e) {
                        const file = e.target.files[0];
                        e.target.value = '';
                        if (!file) return;
                        photoBlob = await shrink(file);
                        this.photoUrl = URL.createObjectURL(photoBlob);
                    },

                    resetPhoto() {
                        if (this.photoUrl) URL.revokeObjectURL(this.photoUrl);
                        photoBlob = null;
                        this.photoUrl = null;
                    },

                    async send() {
                        if (!this.canSend) return;
                        this.sending = true;
                        this.error = '';
                        const data = new FormData(this.$root);
                        if (audioBlob) data.append('audio', audioBlob, 'message-vocal.' + extensionFor(audioBlob.type));
                        if (photoBlob) data.append('photo', photoBlob, 'photo.jpg');

                        try {
                            const res = await fetch(this.$root.action, {
                                method: 'POST',
                                body: data,
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            if (res.ok) {
                                const json = await res.json();
                                beep();
                                navigator.vibrate?.([80, 60, 80]);
                                setTimeout(() => { window.location.href = json.redirect; }, 300);
                                return;
                            }
                            if (res.status === 422) {
                                const json = await res.json();
                                this.error = Object.values(json.errors || {})[0]?.[0] || json.message;
                            } else if (res.status === 413) {
                                this.error = 'Le message est trop lourd. Recommencez plus court.';
                            } else if (res.status === 419) {
                                this.error = 'La page a expiré. Rechargez-la et recommencez.';
                            } else {
                                this.error = 'L\'envoi n\'a pas marché. Réessayez.';
                            }
                        } catch (e) {
                            this.error = 'Pas de réseau. Réessayez quand le wifi revient.';
                        }
                        this.sending = false;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
