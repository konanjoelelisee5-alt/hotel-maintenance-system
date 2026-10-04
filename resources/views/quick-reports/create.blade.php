<x-app-layout crumb="Signalement rapide" page-title="Signaler un problème" :back-route="route(auth()->user()->dashboardRoute())">
    {{-- Pensé pour le personnel d'étage : pictogrammes, gros boutons, message vocal
         plutôt que texte. Envoi en fetch pour joindre l'enregistrement (Blob) au formulaire.
         Présentation en 4 étapes (maquette Claude Design, docs/design-claude/) : les étapes
         ne font que masquer/afficher ; tous les champs restent dans le formulaire. --}}
    @php
        // Pictogrammes (x-nav-icon) des catégories et de l'occupation : présentation seulement.
        $categoryIcons = ['eau' => 'drop', 'electricite' => 'bolt', 'clim' => 'snow', 'tv' => 'tv', 'mobilier' => 'bed', 'porte' => 'key', 'autre' => 'dots'];
        $occupancyIcons = ['libre' => 'check', 'client_absent' => 'luggage', 'client_present' => 'bed', 'depart' => 'door'];
        $steps = ['Lieu', 'Problème', 'Message vocal', 'Photo'];
    @endphp
    <form x-data="quickReport(@js(['rooms' => $rooms, 'commonAreas' => $commonAreas, 'prefill' => $prefillRoom, 'maxSeconds' => $maxSeconds]))"
          @submit.prevent="send" method="POST" action="{{ route('quick-reports.store') }}"
          class="max-w-[640px] w-full mx-auto flex flex-col gap-5 pb-40 lg:pb-0">
        @csrf
        <input type="hidden" name="category" :value="category ?? ''">
        <input type="hidden" name="common_area" :value="commonArea ? 1 : 0">
        <input type="hidden" name="common_area_id" :value="commonArea && commonAreaId ? commonAreaId : ''">
        <input type="hidden" name="urgent" :value="urgent ? 1 : 0">
        <input type="hidden" name="room_occupancy" :value="commonArea ? '' : (occupancy ?? '')">

        {{-- Progression --}}
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-0.5 leading-tight">
                <span class="text-[11px] font-bold tracking-[.12em] text-ink-grey">ÉTAPE <span x-text="step"></span> SUR 4</span>
                <span class="text-[15px] font-bold" x-text="@js($steps)[step - 1]"></span>
            </div>
            <ol class="grid grid-cols-4 gap-1.5" aria-label="Étapes du signalement">
                @foreach ($steps as $i => $label)
                    <li class="h-1 rounded-full transition-colors duration-300" :class="step >= {{ $i + 1 }} ? 'bg-gold' : 'bg-line'">
                        <span class="sr-only">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- 1. Où ? --}}
        <section x-show="step === 1" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <h2 class="text-[26px] sm:text-[32px] font-extrabold tracking-[-.025em] leading-[1.15]">Où se trouve le problème ?</h2>
                <p class="text-[15px] text-[#5C6472]">Tapez le numéro de la chambre ou choisissez un espace commun.</p>
            </div>

            {{-- Affichage du lieu : bordure verte si la chambre existe, rouge sinon. --}}
            <label class="bg-white rounded-[22px] px-[18px] py-4 flex items-center gap-3.5 border-2 transition"
                   :class="(roomKnown || commonArea) ? 'border-green shadow-[0_0_0_5px_rgba(30,122,85,.08)]' : (roomNumber ? 'border-red' : 'border-line')">
                <span class="w-[50px] h-[50px] rounded-[15px] flex items-center justify-center flex-shrink-0"
                      :class="(roomKnown || commonArea) ? 'bg-[#E3F1EA] text-green' : (roomNumber ? 'bg-[#FBE7E5] text-red' : 'bg-line-soft text-navy')">
                    <x-nav-icon name="door" class="!w-7 !h-7" />
                </span>
                <span class="flex-1 min-w-0 flex flex-col gap-1">
                    <span class="text-[13px] font-semibold text-ink-grey">Chambre</span>
                    <input type="text" name="room_number" x-model.trim="roomNumber" @input="commonArea = false"
                           inputmode="numeric" autocomplete="off" placeholder="–––" aria-label="Numéro de chambre"
                           class="w-full p-0 border-0 bg-transparent text-[40px] font-extrabold tracking-[.12em] leading-none tabular-nums text-navy placeholder:text-[#CFC8BA] focus:ring-0">
                </span>
                <span class="text-[13px] font-bold text-right" aria-live="polite">
                    <span x-show="roomKnown" class="text-green">✓ <span x-show="roomFloor" x-text="/^\d+$/.test(roomFloor) ? 'Étage ' + roomFloor : roomFloor"></span></span>
                    <span x-show="roomNumber && !roomKnown && !commonArea" class="text-red">Chambre inconnue</span>
                    <span x-show="!roomNumber && !commonArea" class="text-ink-grey">3 chiffres</span>
                </span>
            </label>

            {{-- Occupation : l'application n'est pas reliée à Opera, c'est l'agent qui sait.
                 Client sorti → réparation avant son retour ; chambre libre → blocage possible. --}}
            <div x-show="roomKnown && !commonArea" class="flex flex-col gap-2">
                <p class="text-[13px] font-bold tracking-[.1em] text-ink-grey">IL Y A UN CLIENT ?</p>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach ($occupancies as $o)
                        <button type="button" @click="occupancy = '{{ $o->value }}'"
                                class="min-h-[64px] rounded-[18px] border-[1.5px] flex items-center gap-2.5 px-3 text-left text-[14px] font-semibold leading-tight transition active:scale-[.97]"
                                :class="occupancy === '{{ $o->value }}' ? 'border-navy bg-navy text-white' : 'border-line bg-white text-navy'">
                            <span class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                  :class="occupancy === '{{ $o->value }}' ? 'bg-[#E4C58F]/15 text-[#E4C58F]' : 'bg-line-soft'">
                                <x-nav-icon :name="$occupancyIcons[$o->value] ?? 'bed'" class="!w-[22px] !h-[22px]" />
                            </span>
                            {{ $o->label() }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-4 pt-1.5">
                <span class="flex-1 h-px bg-line"></span>
                <span class="text-[12px] font-bold tracking-[.1em] text-ink-grey">OU UN ESPACE COMMUN</span>
                <span class="flex-1 h-px bg-line"></span>
            </div>
            <button type="button" @click="commonArea = !commonArea; if (commonArea) roomNumber = ''"
                    class="h-[52px] rounded-2xl border-[1.5px] flex items-center justify-center gap-2.5 text-[15px] font-bold transition"
                    :class="commonArea ? 'border-navy bg-navy text-white' : 'border-navy bg-transparent text-navy'">
                <x-nav-icon name="building" class="!w-5 !h-5" />
                Hall, piscine, espaces communs
            </button>
            {{-- Espaces communs déclarés dans « Lieux » (hors service exclus), + un choix libre. --}}
            <div x-show="commonArea" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                <template x-for="area in commonAreas" :key="area.id">
                    <button type="button" @click="commonAreaId = area.id" x-text="area.label"
                            class="min-h-[56px] rounded-[18px] border-[1.5px] px-2 text-[14px] font-semibold transition active:scale-[.97]"
                            :class="commonAreaId === area.id ? 'border-navy bg-navy text-white' : 'border-line bg-white text-navy'"></button>
                </template>
                <button type="button" @click="commonAreaId = null"
                        class="min-h-[56px] rounded-[18px] border-[1.5px] px-2 text-[14px] font-semibold transition active:scale-[.97]"
                        :class="commonAreaId === null ? 'border-navy bg-navy text-white' : 'border-line bg-white text-navy'">Autre endroit</button>
            </div>
            <p x-show="commonArea && !commonAreaLabel" class="text-[13px] font-medium text-navy">Autre endroit : dites où dans le message vocal.</p>
        </section>

        {{-- Lieu choisi, rappelé en tête des étapes suivantes. --}}
        <div x-show="step > 1" class="flex">
            <span class="inline-flex items-center gap-1.5 h-[30px] pl-2 pr-3 rounded-full bg-navy text-white text-[13px] font-semibold">
                <x-nav-icon name="pin" class="!w-4 !h-4 text-[#E4C58F]" />
                <span x-text="commonArea ? (commonAreaLabel ?? 'Autre endroit') : 'Chambre ' + roomNumber"></span>
            </span>
        </div>

        {{-- 2. Quel problème ? --}}
        <section x-show="step === 2" class="flex flex-col gap-4">
            <h2 class="text-[26px] sm:text-[32px] font-extrabold tracking-[-.025em] leading-[1.15]">Quel est le problème ?</h2>
            <div class="grid grid-cols-2 sm:grid-cols-[repeat(auto-fill,minmax(112px,1fr))] gap-2.5">
                @foreach ($categories as $c)
                    <button type="button" @click="category = '{{ $c->value }}'"
                            class="min-h-[104px] rounded-[18px] border-[1.5px] flex flex-col items-center justify-center gap-2.5 p-2 transition active:scale-[.97]"
                            :class="category === '{{ $c->value }}' ? 'border-navy bg-navy text-white' : 'border-line bg-white text-navy'">
                        <span class="w-11 h-11 rounded-[13px] flex items-center justify-center"
                              :class="category === '{{ $c->value }}' ? 'bg-[#E4C58F]/15 text-[#E4C58F]' : 'bg-line-soft'">
                            <x-nav-icon :name="$categoryIcons[$c->value] ?? 'dots'" class="!w-6 !h-6" />
                        </span>
                        <span class="text-[14px] font-semibold text-center leading-tight">{{ $c->label() }}</span>
                    </button>
                @endforeach
            </div>
            <button type="button" @click="urgent = !urgent" role="switch" :aria-checked="urgent"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-[18px] border-[1.5px] text-left transition"
                    :class="urgent ? 'border-red bg-[#FBE7E5]' : 'border-line bg-white'">
                <span class="w-11 h-11 rounded-[13px] flex items-center justify-center flex-shrink-0 transition"
                      :class="urgent ? 'bg-red text-white' : 'bg-[#FBE7E5] text-red'">
                    <x-nav-icon name="alert" class="!w-6 !h-6" />
                </span>
                <span class="flex-1 flex flex-col gap-0.5">
                    <span class="text-[16px] font-bold text-red">C'est urgent</span>
                    <span class="text-[13px] leading-snug text-[#5C6472]">Eau qui coule, étincelles, odeur de gaz, client bloqué…</span>
                </span>
                <span class="w-[50px] h-[30px] flex-shrink-0 rounded-full p-[3px] flex transition-colors" :class="urgent ? 'bg-red' : 'bg-[#D9D2C5]'">
                    <span class="w-6 h-6 rounded-full bg-white shadow transition-transform" :class="urgent ? 'translate-x-5' : 'translate-x-0'"></span>
                </span>
            </button>
        </section>

        {{-- 3. Le message vocal --}}
        <section x-show="step === 3" class="flex flex-col gap-4">
            <h2 class="text-[26px] sm:text-[32px] font-extrabold tracking-[-.025em] leading-[1.15]">Expliquez avec votre voix</h2>

            <template x-if="micSupported">
                <div class="flex flex-col items-center gap-5 pt-6 w-full">
                    <button type="button" @click="toggleRecord" x-show="recState !== 'recorded'"
                            class="relative w-[168px] h-[168px] rounded-full flex items-center justify-center transition active:scale-[.97]"
                            :class="recState === 'recording'
                                ? 'bg-red text-white shadow-[0_0_0_12px_rgba(179,38,30,.14),0_0_0_26px_rgba(179,38,30,.07),0_20px_40px_-16px_rgba(179,38,30,.6)]'
                                : 'bg-navy text-[#E4C58F] shadow-[0_0_0_12px_rgba(14,33,54,.07),0_0_0_26px_rgba(14,33,54,.04),0_20px_40px_-16px_rgba(14,33,54,.6)]'"
                            :aria-label="recState === 'recording' ? 'Arrêter' : 'Parler'">
                        <span x-show="recState === 'recording'" class="absolute inset-0 rounded-full bg-red animate-ping opacity-30"></span>
                        <span x-show="recState !== 'recording'" class="relative"><x-nav-icon name="mic" class="!w-16 !h-16" /></span>
                        <span x-show="recState === 'recording'" class="relative"><x-nav-icon name="stop" class="!w-14 !h-14 text-white" /></span>
                    </button>
                    <p x-show="recState === 'idle'" class="flex flex-col items-center gap-1 pt-3 text-center">
                        <span class="text-[19px] font-bold">Appuyez pour parler</span>
                        <span class="text-[14px] text-[#5C6472]">Touchez encore pour arrêter · <span x-text="clock(maxSeconds)"></span> maximum</span>
                    </p>
                    <p x-show="recState === 'recording'" class="flex flex-col items-center gap-2">
                        <span class="font-mono text-[36px] font-semibold tabular-nums"><span x-text="clock(seconds)"></span><span class="text-[16px] text-ink-grey"> / <span x-text="clock(maxSeconds)"></span></span></span>
                        <span class="flex items-center gap-2 text-[15px] font-semibold text-red"><span class="w-2 h-2 rounded-full bg-red"></span>Enregistrement · appuyez pour arrêter</span>
                    </p>

                    <div x-show="recState === 'recorded'" class="w-full flex flex-col items-center gap-4">
                        <div class="w-full bg-white border border-line rounded-[20px] p-4 flex flex-col gap-3">
                            <audio :src="audioUrl" controls class="w-full"></audio>
                        </div>
                        <p class="flex items-center gap-2 text-[16px] font-bold text-green">
                            <x-nav-icon name="check" class="!w-5 !h-5" /> Message enregistré (<span x-text="clock(seconds)"></span>)
                        </p>
                        <button type="button" @click="resetAudio" class="h-12 px-[18px] rounded-[14px] border-[1.5px] border-navy flex items-center gap-2 text-[15px] font-bold">
                            <x-nav-icon name="replay" class="!w-5 !h-5" /> Recommencer
                        </button>
                    </div>
                </div>
            </template>

            <p x-show="!micSupported" class="w-full px-4 py-3 rounded-xl bg-[#FBF1DF] text-[#7A5A16] text-[13px]">
                Le micro n'est pas disponible ici (connexion non sécurisée ou navigateur trop ancien). Prenez une photo ou écrivez un message à l'étape suivante.
            </p>
            <p x-show="micError" x-text="micError" class="w-full px-4 py-3 rounded-xl bg-[#FDECEA] text-[#8A1F16] text-[13px]"></p>
        </section>

        {{-- 4. Photo + texte facultatifs, récapitulatif --}}
        <section x-show="step === 4" class="flex flex-col gap-4">
            <div class="flex items-center gap-2.5">
                <h2 class="text-[26px] sm:text-[32px] font-extrabold tracking-[-.025em] leading-[1.15]">Ajouter une photo</h2>
                <span class="h-6 px-2.5 rounded-[7px] bg-[#EAE5DB] text-[#5C6472] text-[12px] font-semibold flex items-center">Facultatif</span>
            </div>
            <div class="grid gap-3.5 sm:grid-cols-2">
                <label x-show="!photoUrl" class="h-[180px] rounded-[20px] border-[1.5px] border-dashed border-[#C4BCAD] bg-white flex flex-col items-center justify-center gap-3 cursor-pointer hover:border-navy transition">
                    <span class="w-16 h-16 rounded-full bg-navy text-[#E4C58F] flex items-center justify-center"><x-nav-icon name="camera" class="!w-8 !h-8" /></span>
                    <span class="text-[16px] font-bold">Prendre une photo</span>
                    <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pickPhoto">
                </label>
                <div x-show="photoUrl" class="relative h-[180px] rounded-[20px] overflow-hidden bg-line-soft">
                    <img :src="photoUrl" alt="Photo jointe" class="w-full h-full object-cover">
                    <span class="absolute left-2.5 top-2.5 flex items-center gap-1 h-7 pl-1.5 pr-2.5 rounded-full bg-green text-white text-[12px] font-bold">
                        <x-nav-icon name="check" class="!w-4 !h-4" /> Ajoutée
                    </span>
                    <button type="button" @click="resetPhoto" class="absolute right-2.5 top-2.5 h-10 px-3 rounded-[10px] bg-white flex items-center gap-1.5 text-[13px] font-bold shadow">
                        <x-nav-icon name="replay" class="!w-4 !h-4" /> Reprendre
                    </button>
                </div>
                <label class="flex flex-col gap-2">
                    <span class="text-[13px] font-semibold text-[#5C6472]">Un mot pour le technicien</span>
                    <textarea name="note" rows="4" maxlength="1000" placeholder="Ex. le client est présent jusqu'à 11 h"
                              class="flex-1 min-h-[96px] rounded-2xl border-line bg-white px-4 py-3.5 text-[15px] resize-none focus:border-navy focus:ring-2 focus:ring-navy/10"></textarea>
                </label>
            </div>

            <div class="flex flex-col gap-2.5">
                <span class="text-[12px] font-bold tracking-[.1em] text-ink-grey">RÉCAPITULATIF</span>
                <dl class="bg-white border border-line rounded-[20px] px-4 py-1">
                    <div class="flex items-center gap-3 py-3 border-b border-line-soft">
                        <span class="w-9 h-9 rounded-[10px] bg-line-soft flex items-center justify-center flex-shrink-0"><x-nav-icon name="pin" class="!w-5 !h-5" /></span>
                        <span class="flex-1 flex flex-col"><dt class="text-[12px] text-ink-grey">Lieu</dt><dd class="text-[15px] font-semibold" x-text="commonArea ? (commonAreaLabel ?? 'Autre endroit') : 'Chambre ' + roomNumber"></dd></span>
                    </div>
                    <div class="flex items-center gap-3 py-3 border-b border-line-soft">
                        <span class="w-9 h-9 rounded-[10px] bg-line-soft flex items-center justify-center flex-shrink-0"><x-nav-icon name="wrench" class="!w-5 !h-5" /></span>
                        <span class="flex-1 flex flex-col"><dt class="text-[12px] text-ink-grey">Problème</dt>
                            <dd class="text-[15px] font-semibold">
                                @foreach ($categories as $c)
                                    <span x-show="category === '{{ $c->value }}'">{{ $c->label() }}</span>
                                @endforeach
                                <span x-show="urgent" class="text-red"> · Urgent</span>
                            </dd>
                        </span>
                    </div>
                    <div class="flex items-center gap-3 py-3 border-b border-line-soft">
                        <span class="w-9 h-9 rounded-[10px] bg-line-soft flex items-center justify-center flex-shrink-0"><x-nav-icon name="mic" class="!w-5 !h-5" /></span>
                        <span class="flex-1 flex flex-col"><dt class="text-[12px] text-ink-grey">Message vocal</dt><dd class="text-[15px] font-semibold" x-text="recState === 'recorded' ? clock(seconds) : 'Aucun'"></dd></span>
                    </div>
                    <div class="flex items-center gap-3 py-3">
                        <span class="w-9 h-9 rounded-[10px] bg-line-soft flex items-center justify-center flex-shrink-0"><x-nav-icon name="camera" class="!w-5 !h-5" /></span>
                        <span class="flex-1 flex flex-col"><dt class="text-[12px] text-ink-grey">Photo</dt><dd class="text-[15px] font-semibold" x-text="photoUrl ? '1 photo' : 'Aucune'"></dd></span>
                    </div>
                </dl>
            </div>
        </section>

        <p x-show="error" x-text="error" class="px-4 py-3 rounded-xl bg-[#FDECEA] text-[#8A1F16] text-[14px] font-medium"></p>

        {{-- Barre d'action : collée au-dessus de la barre de navigation mobile (64 px). --}}
        <div class="fixed lg:static bottom-[64px] inset-x-0 z-30 px-4 lg:px-0 pt-3 pb-3 lg:py-0 bg-canvas/95 backdrop-blur lg:bg-transparent border-t border-line lg:border-0">
            <div class="max-w-[640px] mx-auto flex items-center gap-2.5">
                <button type="button" x-show="step > 1" @click="prev"
                        class="h-14 px-5 rounded-2xl border-[1.5px] border-navy text-[16px] font-bold flex-shrink-0">Retour</button>
                <button type="button" x-show="step < 4" @click="next" :disabled="!stepOk"
                        class="flex-1 h-14 rounded-2xl bg-navy text-white flex items-center justify-center gap-2.5 text-[17px] font-bold shadow-[0_12px_24px_-12px_rgba(14,33,54,.6)] disabled:bg-[#DCD5C8] disabled:text-ink-grey disabled:shadow-none">
                    <span x-text="step === 3 && recState !== 'recorded' ? 'Continuer sans message' : 'Continuer'"></span>
                    <x-nav-icon name="arrow-right" class="!w-5 !h-5" />
                </button>
                <button type="submit" x-show="step === 4" :disabled="!canSend"
                        class="flex-1 h-14 rounded-2xl flex items-center justify-center gap-2.5 text-[17px] font-bold text-white disabled:opacity-40"
                        :class="urgent ? 'bg-red' : 'bg-green'">
                    <span x-show="sending" class="w-6 h-6 rounded-full border-[3px] border-white/40 border-t-white animate-spin"></span>
                    <span x-text="sending ? 'Envoi…' : 'Envoyer le signalement'"></span>
                    <x-nav-icon name="send" class="!w-5 !h-5" x-show="!sending" />
                </button>
            </div>
            <p x-show="!stepOk && !sending" class="max-w-[640px] mx-auto mt-1.5 text-center text-[12px] text-ink-grey">
                <span x-show="step === 1 && !commonArea && !roomKnown">Indiquez la chambre</span>
                <span x-show="step === 1 && !commonArea && roomKnown && !occupancy">Dites s'il y a un client</span>
                <span x-show="step === 2 && !category">Choisissez le problème</span>
                <span x-show="step === 3 && recState === 'recording'">Arrêtez l'enregistrement</span>
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
                    // Étape affichée (1 Lieu, 2 Problème, 3 Message vocal, 4 Photo) : présentation seulement.
                    step: 1,

                    get roomKnown() { return this.roomNumber !== '' && Object.prototype.hasOwnProperty.call(this.rooms, this.roomNumber); },
                    get roomFloor() { return this.roomKnown ? this.rooms[this.roomNumber] : null; },
                    get commonAreaLabel() { return this.commonAreas.find((a) => a.id === this.commonAreaId)?.label ?? null; },
                    get placeOk() { return this.commonArea || (this.roomKnown && !!this.occupancy); },
                    get canSend() { return this.placeOk && !!this.category && !this.sending && this.recState !== 'recording'; },

                    // Peut-on passer à l'étape suivante ? Mêmes règles que canSend, étape par étape.
                    get stepOk() {
                        return [this.placeOk, !!this.category, this.recState !== 'recording', this.canSend][this.step - 1];
                    },
                    next() { if (this.stepOk && this.step < 4) { this.step++; window.scrollTo({ top: 0 }); } },
                    prev() { if (this.step > 1) { if (this.recState === 'recording') this.stopRecord(); this.step--; window.scrollTo({ top: 0 }); } },

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
