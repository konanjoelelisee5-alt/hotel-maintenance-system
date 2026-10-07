{{-- « Ajouter une précision » à un signalement en cours : quelques mots, un message
     vocal (2 min au plus) et/ou une photo. Ouvert par l'événement hk-complement-open
     (bouton de l'en-tête de la fiche). Envoi en fetch pour joindre l'enregistrement. --}}
<section x-data="hkComplement({ action: @js(route('quick-reports.complement', $workOrder)), maxSeconds: {{ \App\Http\Requests\StoreQuickReportRequest::MAX_AUDIO_SECONDS }} })"
         @hk-complement-open.window="open = true; $nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
         {{-- #completer : arrivée depuis l'avertissement de doublon du signalement. --}}
         x-init="if (location.hash === '#completer') { open = true; $nextTick(() => $el.scrollIntoView({ block: 'center' })) }"
         x-show="open" x-cloak class="bg-white border border-line rounded-xl overflow-hidden">
    <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
        <span class="w-8 h-8 rounded-[8px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0"><x-hk.icon name="plus" :size="16" /></span>
        <div class="min-w-0 flex-1">
            <h3 class="m-0 text-[14.5px] font-semibold text-navy">Ajouter une précision</h3>
            <p class="m-0 text-[12.5px] text-ink-muted">Le technicien la verra dans sa fiche. Rien de ce qui a été envoyé n'est modifié.</p>
        </div>
        <button type="button" class="w-9 h-9 rounded-lg flex items-center justify-center text-ink-grey hover:bg-paper" @click="close" aria-label="Fermer"><x-hk.icon name="x" :size="16" /></button>
    </header>

    <div class="flex flex-col gap-4 px-5 py-4">
        <textarea x-model="note" rows="2" maxlength="1000" placeholder="Ex. : la fuite a empiré, l'eau coule dans le couloir."
                  aria-label="Précision" class="w-full rounded-[10px] border-line text-[14px] focus:border-navy focus:ring-navy/20"></textarea>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Message vocal --}}
            <template x-if="micSupported">
                <div class="flex items-center gap-2.5">
                    <button type="button" x-show="rec !== 'done'" @click="toggleRecord" class="btn"
                            :class="rec === 'on' ? 'btn-danger-solid' : 'btn-secondary'">
                        <span x-show="rec !== 'on'"><x-hk.icon name="mic" :size="16" /></span>
                        <span x-show="rec === 'on'" x-cloak><x-hk.icon name="stop" :size="14" /></span>
                        <span x-text="rec === 'on' ? 'Arrêter · ' + clock(seconds) : 'Message vocal'"></span>
                    </button>
                    <template x-if="rec === 'done'">
                        <div class="flex items-center gap-2">
                            <audio :src="audioUrl" controls class="h-10 max-w-[240px]"></audio>
                            <button type="button" @click="resetAudio" class="w-9 h-9 rounded-lg flex items-center justify-center text-ink-grey hover:bg-paper" aria-label="Supprimer le message vocal"><x-hk.icon name="trash" :size="16" /></button>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Photo --}}
            <label x-show="!photoUrl" class="btn btn-secondary cursor-pointer">
                <x-hk.icon name="camera" :size="16" /> Photo
                <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pickPhoto">
            </label>
            <div x-show="photoUrl" x-cloak class="flex items-center gap-2">
                <img :src="photoUrl" alt="Photo jointe" class="w-12 h-12 rounded-[8px] object-cover border border-line">
                <button type="button" @click="resetPhoto" class="w-9 h-9 rounded-lg flex items-center justify-center text-ink-grey hover:bg-paper" aria-label="Retirer la photo"><x-hk.icon name="trash" :size="16" /></button>
            </div>
        </div>

        <p x-show="error" x-cloak x-text="error" class="m-0 px-3.5 py-2.5 rounded-lg bg-danger-bg text-danger-ink text-[13px]"></p>

        <div class="flex flex-wrap gap-2.5">
            <button type="button" @click="send" :disabled="!canSend" :data-state="btnState || null" class="btn btn-primary">
                <x-hk.icon name="send" :size="16" x-show="btnState !== 'success'" />
                <x-hk.icon name="check" :size="16" x-show="btnState === 'success'" x-cloak />
                <span x-text="btnState === 'success' ? 'Envoyé' : 'Envoyer la précision'">Envoyer la précision</span>
            </button>
            <button type="button" class="btn btn-ghost" @click="close">Annuler</button>
        </div>
    </div>
</section>

@pushOnce('scripts')
    <script>
        function hkComplement(config) {
            // Enregistreur et fichiers gardés hors de l'état Alpine (un Proxy les casse).
            let recorder = null, stream = null, chunks = [], timer = null, audioBlob = null, photoBlob = null;
            const extensionFor = (type) => window.hkMedia.extensionFor(type);
            const shrink = (file) => window.hkMedia.shrink(file);

            return {
                open: false,
                note: '',
                micSupported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder),
                rec: 'idle',
                seconds: 0,
                audioUrl: null,
                photoUrl: null,
                sending: false,
                btnState: '', // état du bouton d'envoi (.btn data-state) : loading, success, error
                error: '',

                get canSend() { return !this.sending && this.rec !== 'on' && (this.note.trim() !== '' || !!this.audioUrl || !!this.photoUrl); },
                clock(s) { return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); },

                async toggleRecord() {
                    if (this.rec === 'on') return this.stopRecord();
                    this.error = '';
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    } catch (e) {
                        this.error = 'Le micro est bloqué. Autorisez le micro pour ce site dans le navigateur.';
                        return;
                    }
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
                stopRecord() {
                    clearInterval(timer);
                    if (recorder && recorder.state !== 'inactive') recorder.stop();
                },
                resetAudio() {
                    if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                    audioBlob = null; this.audioUrl = null; this.seconds = 0; this.rec = 'idle';
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
                    photoBlob = null; this.photoUrl = null;
                },
                close() {
                    if (this.rec === 'on') this.stopRecord();
                    this.open = false;
                },

                async send() {
                    if (!this.canSend) return;
                    this.sending = true;
                    this.btnState = 'loading';
                    this.error = '';
                    const data = new FormData();
                    data.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    if (this.note.trim() !== '') data.append('note', this.note.trim());
                    if (audioBlob) data.append('audio', audioBlob, 'precision-vocale.' + extensionFor(audioBlob.type));
                    if (photoBlob) data.append('photo', photoBlob, 'photo.jpg');
                    try {
                        const res = await fetch(config.action, {
                            method: 'POST', body: data, credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (res.ok) { this.btnState = 'success'; window.location.href = (await res.json()).redirect; return; }
                        if (res.status === 422) {
                            const json = await res.json();
                            this.error = Object.values(json.errors || {})[0]?.[0] || json.message;
                        } else if (res.status === 403) {
                            this.error = 'Ce signalement ne peut plus être complété (il est déjà réparé ou clos).';
                        } else {
                            this.error = 'L\'envoi n\'a pas marché. Réessayez.';
                        }
                    } catch (e) {
                        this.error = 'Pas de réseau. Réessayez quand le wifi revient.';
                    }
                    this.sending = false;
                    this.btnState = 'error';
                    setTimeout(() => { if (this.btnState === 'error') this.btnState = ''; }, 900);
                },
            };
        }
    </script>
@endPushOnce
