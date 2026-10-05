{{-- Signalement Housekeeping en 4 étapes : ① lieu, ② problème, ③ message vocal, ④ photo,
     mot et récapitulatif. « Continuer » reste grisé tant que l'étape n'est pas complète.
     L'étape vit dans l'adresse (#etape-2) : le bouton retour du téléphone revient à
     l'étape précédente. Envoi en fetch pour joindre l'enregistrement (Blob).
     Style : celui des fiches de l'admin (panneaux blancs, icônes au trait, .btn). --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $steps = ['Lieu', 'Problème', 'Message vocal', 'Photo et envoi'];
    $categoryData = collect($categories)->map(fn ($c) => ['value' => $c->value, 'label' => $hk::categoryLabel($c)])->values();
    $occupancyData = collect($occupancies)->map(fn ($o) => ['value' => $o->value, 'label' => $o->label()])->values();
    // Carte d'option (lieu, client, catégorie) : sélection = bord marine + coche.
    $option = 'relative w-full rounded-xl border bg-white text-left flex items-center gap-3 px-3.5 py-3 transition hover:border-navy/40';
    $optionOn = "'border-navy ring-1 ring-navy bg-paper'";
    $tile = 'w-9 h-9 rounded-[9px] border flex items-center justify-center flex-shrink-0 transition';
    $panel = 'bg-white border border-line rounded-xl';
@endphp

<x-app-layout crumb="Signalement" page-title="Signaler un problème" focus :back-route="route(auth()->user()->dashboardRoute())">
    <form x-data="quickReport(@js([
              'rooms' => $rooms, 'outOfService' => $outOfServiceRooms, 'commonAreas' => $commonAreas,
              'categories' => $categoryData, 'occupancies' => $occupancyData,
              'prefill' => $prefillRoom, 'maxSeconds' => $maxSeconds, 'openReports' => $openReports,
          ]))"
          @submit.prevent="send" method="POST" action="{{ route('quick-reports.store') }}"
          @keydown.window="typeKey($event)"
          class="grid gap-5 desk:grid-cols-[minmax(0,1fr)_340px] items-start pb-28 desk:pb-0">
        @csrf
        <input type="hidden" name="room_number" :value="commonArea ? '' : roomNumber">
        <input type="hidden" name="category" :value="category ?? ''">
        <input type="hidden" name="common_area" :value="commonArea ? 1 : 0">
        <input type="hidden" name="common_area_id" :value="commonArea && commonAreaId ? commonAreaId : ''">
        <input type="hidden" name="urgent" :value="urgent ? 1 : 0">
        <input type="hidden" name="room_occupancy" :value="commonArea ? '' : (occupancy ?? '')">

        <div class="flex flex-col gap-5 min-w-0 w-full max-w-[760px]">
            {{-- Progression : barre sur téléphone, étapes numérotées à partir de la tablette --}}
            <div class="tab:hidden flex flex-col gap-2">
                <div class="flex items-center justify-between text-[12.5px]">
                    <span class="font-semibold text-navy" x-text="stepNames[step - 1]">Lieu</span>
                    <span class="font-mono text-ink-grey">Étape <span x-text="step">1</span>/4</span>
                </div>
                <div class="h-1 rounded-full bg-line overflow-hidden"><div class="h-full bg-navy transition-all" :style="'width:' + step * 25 + '%'"></div></div>
            </div>
            <ol class="hidden tab:flex m-0 p-0 list-none items-center gap-2 {{ $panel }} px-5 py-3.5" aria-label="Étapes">
                @foreach ($steps as $i => $name)
                    <li class="flex items-center gap-2.5 {{ $loop->last ? '' : 'flex-1' }}">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-[12px] font-semibold font-mono flex-shrink-0 transition"
                              :class="step > {{ $i + 1 }} ? 'bg-navy text-white' : (step === {{ $i + 1 }} ? 'bg-white text-navy ring-2 ring-gold' : 'bg-line-soft text-ink-grey')">
                            <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                            <span x-show="step > {{ $i + 1 }}" x-cloak><x-hk.icon name="check" :size="14" /></span>
                        </span>
                        <span class="text-[13px] whitespace-nowrap" :class="step === {{ $i + 1 }} ? 'font-semibold text-navy' : 'font-medium text-ink-grey'">{{ $name }}</span>
                        @unless ($loop->last)<span class="flex-1 h-px bg-line ml-1"></span>@endunless
                    </li>
                @endforeach
            </ol>

            {{-- ① Lieu --}}
            <section x-show="step === 1" class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Où se trouve la panne ?</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">Saisissez le numéro de la chambre ou choisissez un espace commun.</p>
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

                {{-- Chambre : pavé numérique, validation en direct --}}
                <div x-show="!commonArea" class="grid gap-4 split:grid-cols-[minmax(0,1fr)_300px] items-start">
                    <div class="flex flex-col gap-3 min-w-0">
                        <div class="{{ $panel }} px-4 py-3.5 flex items-center gap-4 transition"
                             :class="{ '!border-green ring-1 ring-green/30': roomState === 'valid', '!border-red ring-1 ring-red/25': roomState === 'unknown', '!border-amber ring-1 ring-amber/25': roomState === 'out' }">
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] font-semibold uppercase tracking-wide text-ink-grey">Numéro de chambre</div>
                                <div class="mt-0.5 font-mono text-[34px] font-medium leading-tight tracking-[.08em] text-navy min-h-[44px]" aria-live="polite">
                                    <span x-text="roomNumber">{{ $prefillRoom }}</span><span x-show="roomNumber === ''" class="text-[#C9C3B6]">———</span>
                                </div>
                            </div>
                            <button type="button" @click="openScanner" class="btn btn-secondary flex-shrink-0" aria-label="Scanner le QR de la chambre">
                                <x-hk.icon name="scan" :size="17" /> <span class="hidden min-[400px]:inline">Scanner le QR</span>
                            </button>
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
                                <span class="text-ink-grey">Saisissez le numéro ou scannez le QR de la porte.</span>
                            </template>
                        </p>

                        {{-- Occupation : l'application n'est pas reliée à Opera, c'est l'agent qui sait. --}}
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

                    <div class="grid grid-cols-3 gap-2 w-full max-w-[360px] mx-auto split:mx-0" aria-label="Pavé numérique">
                        @foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $digit)
                            <button type="button" @click="press('{{ $digit }}')" class="h-14 rounded-xl bg-white border border-line font-mono text-[22px] text-navy hover:bg-paper active:bg-line-soft">{{ $digit }}</button>
                        @endforeach
                        <button type="button" @click="roomNumber = ''" class="h-14 rounded-xl bg-line-soft border border-line text-[13px] font-medium text-[#6C6658] hover:bg-line" aria-label="Effacer le numéro">Effacer</button>
                        <button type="button" @click="press('0')" class="h-14 rounded-xl bg-white border border-line font-mono text-[22px] text-navy hover:bg-paper active:bg-line-soft">0</button>
                        <button type="button" @click="roomNumber = roomNumber.slice(0, -1)" class="h-14 rounded-xl bg-line-soft border border-line flex items-center justify-center text-[#6C6658] hover:bg-line" aria-label="Effacer un chiffre">
                            <x-hk.icon name="backspace" :size="22" />
                        </button>
                    </div>
                </div>

                {{-- Espaces communs déclarés dans « Lieux » (hors service exclus), + un choix libre. --}}
                <div x-show="commonArea" class="grid grid-cols-1 min-[420px]:grid-cols-2 split:grid-cols-3 gap-2">
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
                    <p x-show="commonAreaId === null" class="col-span-full m-0 text-[12.5px] text-[#6C6658]">Précisez l'endroit dans le message vocal (étape 3) ou dans le mot (étape 4).</p>
                </div>
                {{-- Déjà signalé ici : évite qu'un deuxième agent signale la même panne. --}}
                <div x-show="placeReports.length" x-cloak class="flex flex-col gap-2 px-4 py-3 rounded-xl border border-[#26496B]/20 bg-[#EAF0F6]">
                    <p class="m-0 flex items-center gap-2 text-[13px] font-semibold text-[#26496B]">
                        <x-hk.icon name="info" :size="16" />
                        <span x-text="placeReports.length > 1 ? placeReports.length + ' signalements déjà en cours ici' : 'Un signalement déjà en cours ici'"></span>
                    </p>
                    <ul class="m-0 p-0 list-none flex flex-col gap-1">
                        <template x-for="r in placeReports" :key="r.code">
                            <li class="flex flex-wrap items-center gap-x-2 text-[13px] text-[#26496B]">
                                <span class="font-semibold" x-text="r.label"></span>
                                <span class="font-mono text-[12px]" x-text="r.code"></span>
                                <span class="text-[12px] opacity-80" x-text="'· ' + r.ago + (r.assigned ? ' · technicien affecté' : '')"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </section>

            {{-- ② Problème --}}
            <section x-show="step === 2" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Quel est le problème ?</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">La maintenance précisera le diagnostic sur place.</p>
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
                <div x-show="duplicate" x-cloak class="flex flex-col gap-3 px-4 py-3.5 rounded-xl border border-amber/40 bg-[#FBF1DF]">
                    <div class="flex items-start gap-2.5 text-[#7A5A16]">
                        <x-hk.icon name="alert-triangle" :size="18" class="mt-0.5" />
                        <p class="m-0 text-[13px] leading-relaxed">
                            <strong>Déjà signalé :</strong> <span x-text="duplicate ? duplicate.label + ' · ' + duplicate.code + ', ' + duplicate.ago + (duplicate.assigned ? ' (technicien affecté)' : '') : ''"></span>.
                            La maintenance est déjà prévenue. S'il s'agit d'un autre problème, continuez.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a x-show="duplicate && duplicate.url" :href="duplicate ? duplicate.url : '#'" class="btn btn-sm btn-primary"><x-hk.icon name="plus" :size="14" /> Compléter ce signalement</a>
                        <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-sm btn-secondary">C'est le même, ne rien envoyer</a>
                    </div>
                </div>

                <button type="button" role="switch" :aria-checked="urgent" @click="urgent = !urgent"
                        class="{{ $panel }} w-full flex items-center gap-3.5 px-4 py-3.5 text-left transition"
                        :class="urgent && '!border-red/40 bg-[#FDF3F2]'">
                    <span class="{{ $tile }}" :class="urgent ? 'bg-red border-red text-white' : 'bg-[#FBE4E1] border-red/20 text-red'"><x-hk.icon name="alert-triangle" :size="18" /></span>
                    <span class="flex flex-col flex-1 min-w-0">
                        <span class="text-[14.5px] font-semibold" :class="urgent ? 'text-red' : 'text-navy'">Urgence</span>
                        <span class="text-[12.5px] text-[#6C6658]">Eau qui coule, étincelles, odeur de gaz, client bloqué…</span>
                    </span>
                    <span class="relative w-11 h-6 rounded-full flex-shrink-0 transition-colors" :class="urgent ? 'bg-red' : 'bg-[#D6D0C4]'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform" :class="urgent ? 'translate-x-5' : ''"></span>
                    </span>
                </button>
            </section>

            {{-- ③ Message vocal --}}
            <section x-show="step === 3" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Expliquez avec votre voix</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">Facultatif · 2 minutes au plus. Vous pouvez continuer sans message.</p>
                </div>

                <div class="{{ $panel }} px-5 py-6 tab:py-8 flex flex-col items-center gap-5">
                    <template x-if="micSupported">
                        <div class="w-full flex flex-col items-center gap-5">
                            {{-- Forme d'onde : en direct pendant l'enregistrement, puis progression de la réécoute. --}}
                            <div class="w-full max-w-[440px] h-14 flex items-center justify-center gap-[3px]" aria-hidden="true">
                                <template x-for="(level, i) in bars" :key="i">
                                    <span class="w-[4px] rounded-full transition-[height] duration-100"
                                          :class="recState === 'recording' ? 'bg-red/80' : (recState === 'recorded' && i / bars.length < playProgress ? 'bg-navy' : (recState === 'recorded' ? 'bg-navy/25' : 'bg-line'))"
                                          :style="'height:' + Math.max(4, Math.round(level * 56)) + 'px'"></span>
                                </template>
                            </div>

                            <div class="font-mono text-[24px] font-medium tabular-nums" :class="recState === 'recording' ? 'text-red' : 'text-navy'">
                                <span x-text="clock(recState === 'recorded' ? Math.round(playProgress * seconds) : seconds)">0:00</span>
                                <span class="text-[14px] text-ink-grey">/ <span x-text="clock(recState === 'recorded' ? seconds : maxSeconds)"></span></span>
                            </div>

                            <div x-show="recState !== 'recorded'" class="flex flex-col items-center gap-3">
                                <button type="button" @click="toggleRecord"
                                        class="relative w-[88px] h-[88px] rounded-full flex items-center justify-center text-white shadow-[0_12px_28px_-10px_rgba(14,33,54,.55)] transition"
                                        :class="recState === 'recording' ? 'bg-red' : 'bg-navy hover:bg-navy-light'"
                                        :aria-label="recState === 'recording' ? 'Arrêter l\'enregistrement' : 'Enregistrer un message'">
                                    <span x-show="recState === 'recording'" class="absolute -inset-1.5 rounded-full border-2 border-red/40 animate-pulse"></span>
                                    <span x-show="recState !== 'recording'"><x-hk.icon name="mic" :size="32" /></span>
                                    <span x-show="recState === 'recording'" x-cloak><x-hk.icon name="stop" :size="28" /></span>
                                </button>
                                <p class="m-0 text-[13px] text-[#6C6658] text-center" x-text="recState === 'recording' ? 'Enregistrement… touchez pour arrêter' : 'Touchez le micro et parlez'"></p>
                            </div>

                            <div x-show="recState === 'recorded'" class="flex flex-wrap items-center justify-center gap-2.5">
                                <button type="button" @click="togglePlay" class="btn btn-primary btn-lg">
                                    <span x-show="!playing"><x-hk.icon name="play" :size="16" /></span>
                                    <span x-show="playing" x-cloak><x-hk.icon name="pause" :size="16" /></span>
                                    <span x-text="playing ? 'Pause' : 'Réécouter'"></span>
                                </button>
                                <button type="button" @click="resetAudio" class="btn btn-secondary btn-lg"><x-hk.icon name="rotate" :size="16" /> Recommencer</button>
                            </div>
                            <audio x-ref="player" :src="audioUrl" preload="auto" class="hidden"
                                   @timeupdate="playProgress = seconds ? Math.min(1, $event.target.currentTime / seconds) : 0"
                                   @ended="playing = false; playProgress = 0" @pause="playing = false" @play="playing = true"></audio>
                        </div>
                    </template>

                    <p x-show="!micSupported" class="m-0 w-full px-4 py-3 rounded-lg bg-[#FBF1DF] text-[#7A5A16] text-[13px]">
                        Le micro n'est pas disponible ici (connexion non sécurisée ou navigateur trop ancien). Ajoutez une photo ou un mot à l'étape suivante.
                    </p>
                    <p x-show="micError" x-text="micError" class="m-0 w-full px-4 py-3 rounded-lg bg-[#FDECEA] text-[#8A1F16] text-[13px]"></p>
                </div>
            </section>

            {{-- ④ Photo, mot, récapitulatif --}}
            <section x-show="step === 4" x-cloak class="flex flex-col gap-4">
                <div>
                    <h2 class="m-0 text-[19px] font-semibold text-navy tracking-tight">Photo et précisions</h2>
                    <p class="m-0 mt-0.5 text-[13px] text-[#6C6658]">Facultatif. Vérifiez le récapitulatif puis envoyez.</p>
                </div>

                <div class="{{ $panel }} p-4 flex flex-col gap-3">
                    <div class="text-[13px] font-semibold text-navy">Photo</div>
                    <label x-show="!photoUrl" class="min-h-[84px] rounded-[10px] border-2 border-dashed border-line bg-paper/60 flex flex-col items-center justify-center gap-1 text-center cursor-pointer hover:border-navy/40 hover:bg-paper">
                        <span class="flex items-center gap-2 text-[13.5px] font-semibold text-navy"><x-hk.icon name="camera" :size="18" class="text-gold" /> Prendre une photo</span>
                        <span class="text-[11.5px] text-ink-grey">ou choisir une image</span>
                        <input type="file" accept="image/*" capture="environment" class="sr-only" @change="pickPhoto">
                    </label>
                    <div x-show="photoUrl" class="flex items-center gap-3">
                        <img :src="photoUrl" alt="Photo jointe" class="w-20 h-20 object-cover rounded-[10px] border border-line">
                        <button type="button" @click="resetPhoto" class="btn btn-secondary"><x-hk.icon name="trash" :size="16" /> Retirer</button>
                    </div>
                </div>

                <div class="{{ $panel }} p-4 flex flex-col gap-2">
                    <label for="note" class="text-[13px] font-semibold text-navy">Précisions</label>
                    <textarea id="note" name="note" rows="3" maxlength="1000" x-model="note" placeholder="Ex. : la fuite vient du siphon du lavabo."
                              class="w-full rounded-[10px] border-line text-[14px] focus:border-navy focus:ring-navy/20"></textarea>
                </div>

                <div class="desk:hidden">@include('quick-reports.partials.recap')</div>
            </section>

            <p x-show="error" x-cloak x-text="error" class="m-0 px-4 py-3 rounded-lg bg-[#FDECEA] text-[#8A1F16] text-[13.5px] font-medium"></p>

            {{-- Actions : collées en bas sur téléphone et tablette (la navigation est masquée). --}}
            <div class="fixed desk:static bottom-0 inset-x-0 z-30 px-4 tab:px-6 desk:px-0 pt-3 pb-[calc(12px+env(safe-area-inset-bottom))] desk:py-0 bg-white desk:bg-transparent border-t border-line desk:border-0 shadow-[0_-8px_24px_-16px_rgba(14,33,54,.35)] desk:shadow-none">
                <div class="max-w-[760px] flex gap-2.5">
                    <button type="button" x-show="step > 1" @click="back" class="btn btn-secondary btn-lg !h-12">
                        <x-hk.icon name="arrow-left" :size="16" /> Retour
                    </button>
                    <button type="button" x-show="step < 4" @click="next" :disabled="!stepValid" class="btn btn-primary btn-lg !h-12 flex-1">
                        Continuer <x-hk.icon name="arrow-right" :size="16" />
                    </button>
                    <button type="submit" x-show="step === 4" x-cloak :disabled="!canSend" class="btn btn-lg !h-12 flex-1 bg-green text-white hover:bg-[#186446]">
                        <span x-show="!sending"><x-hk.icon name="send" :size="16" /></span>
                        <span x-show="sending" class="w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin"></span>
                        <span x-text="sending ? 'Envoi…' : 'Envoyer le signalement'">Envoyer le signalement</span>
                    </button>
                </div>
                <p x-show="!stepValid && step < 4" class="m-0 mt-1.5 text-center desk:text-left text-[12px] text-ink-grey" x-text="hint"></p>
            </div>
        </div>

        {{-- Ordinateur : récapitulatif fixé à droite pendant tout le parcours --}}
        <aside class="hidden desk:block sticky top-[88px]">@include('quick-reports.partials.recap')</aside>

        {{-- Hors ligne : signalement gardé dans le téléphone (boîte d'envoi, resources/js/hk-outbox.js). --}}
        <div x-show="queued" x-cloak class="fixed inset-0 z-[80] bg-navy-dark/60 backdrop-blur-[2px] flex items-end tab:items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="queued-title">
            <div class="w-full max-w-[420px] bg-white rounded-xl border border-line p-6 flex flex-col items-center gap-3 text-center">
                <span class="w-14 h-14 rounded-full bg-[#FBF1DF] text-amber flex items-center justify-center"><x-hk.icon name="cloud-off" :size="26" /></span>
                <h2 id="queued-title" class="m-0 text-[17px] font-semibold text-navy">Pas de réseau pour le moment</h2>
                <p class="m-0 text-[13.5px] text-[#6C6658] leading-relaxed">Votre signalement est gardé dans ce téléphone, avec le message vocal et la photo. Il partira tout seul dès que le réseau revient.</p>
                <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-primary btn-lg w-full mt-2">Retour à l'accueil</a>
            </div>
        </div>

        {{-- Scan du QR de la chambre --}}
        <div x-show="scanning" x-cloak class="fixed inset-0 z-[80] bg-navy-dark/95 flex flex-col items-center justify-center gap-5 p-6" role="dialog" aria-modal="true" aria-label="Scanner le QR de la chambre">
            <div class="relative w-full max-w-[340px] aspect-square rounded-xl overflow-hidden bg-black">
                <video x-ref="video" class="w-full h-full object-cover" muted playsinline></video>
                <span class="absolute inset-[18%] border-2 border-white/80 rounded-xl"></span>
            </div>
            <p class="m-0 text-white text-[14px] text-center" x-text="scanError || 'Visez le QR code collé sur la porte de la chambre.'"></p>
            <button type="button" @click="closeScanner" class="btn btn-secondary btn-lg"><x-hk.icon name="x" :size="16" /> Annuler</button>
        </div>
    </form>


    @push('scripts')
        <script>
            function quickReport(config) {
                // Objets navigateur gardés hors de l'état Alpine : un MediaRecorder ou un
                // Blob enveloppé dans un Proxy réactif ne fonctionne plus.
                let recorder = null, stream = null, chunks = [], timer = null;
                let audioCtx = null, sampler = null, levels = [];
                let audioBlob = null, photoBlob = null, stopScan = null;
                const BARS = 40;

                const extensionFor = (type) => type.includes('mp4') ? 'm4a' : (type.includes('ogg') ? 'ogg' : 'webm');
                const flat = () => Array(BARS).fill(0.1);
                // Moyenne par paquets : toute la durée tient dans BARS barres.
                const downsample = (values) => {
                    if (!values.length) return flat();
                    return Array.from({ length: BARS }, (_, i) => {
                        const from = Math.floor(i * values.length / BARS), to = Math.max(from + 1, Math.floor((i + 1) * values.length / BARS));
                        const slice = values.slice(from, to);
                        return slice.reduce((a, b) => a + b, 0) / slice.length;
                    });
                };

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
                    stepNames: ['Lieu', 'Problème', 'Message vocal', 'Photo et envoi'],
                    step: 1,
                    rooms: config.rooms,
                    outOfService: config.outOfService.map(String),
                    openReports: config.openReports || [],
                    commonAreas: config.commonAreas,
                    categories: config.categories,
                    occupancies: config.occupancies,
                    roomNumber: config.prefill || '',
                    commonArea: false,
                    commonAreaId: null,
                    category: null,
                    occupancy: null,
                    urgent: false,
                    note: '',
                    micSupported: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder),
                    micError: '',
                    recState: 'idle',
                    seconds: 0,
                    maxSeconds: config.maxSeconds,
                    bars: flat(),
                    audioUrl: null,
                    playing: false,
                    playProgress: 0,
                    photoUrl: null,
                    scanning: false,
                    scanError: '',
                    sending: false,
                    queued: false,
                    error: '',

                    init() {
                        // Étape dans l'adresse : le retour du navigateur revient d'une étape.
                        history.replaceState({ step: 1 }, '', '#etape-1');
                        window.addEventListener('popstate', (e) => {
                            const wanted = e.state?.step ?? 1;
                            if (this.recState === 'recording') this.stopRecord();
                            this.step = Math.min(wanted, this.step);
                        });
                    },

                    // ----- Lieu -----
                    get roomState() {
                        const n = this.roomNumber;
                        if (n === '') return '';
                        if (Object.prototype.hasOwnProperty.call(this.rooms, n)) return 'valid';
                        if (this.outOfService.includes(n)) return 'out';
                        // Début d'un numéro existant (« 21 » pour 214) : on attend la suite.
                        const prefix = Object.keys(this.rooms).concat(this.outOfService).some((r) => r.startsWith(n));
                        return prefix ? 'typing' : 'unknown';
                    },
                    get roomFloor() { return this.roomState === 'valid' ? this.rooms[this.roomNumber] : null; },
                    get commonAreaLabel() { return this.commonAreas.find((a) => a.id === this.commonAreaId)?.label ?? 'Autre endroit'; },
                    // Signalements déjà ouverts au lieu choisi (même clé que Housekeeping::openReportsByPlace).
                    get placeKey() {
                        if (this.commonArea) return this.commonAreaId ? 'area:' + this.commonAreaId : null;
                        return this.roomState === 'valid' ? this.roomNumber : null;
                    },
                    get placeReports() { return this.placeKey ? this.openReports.filter((r) => r.place === this.placeKey) : []; },
                    get duplicate() { return this.category ? this.placeReports.find((r) => r.category === this.category) ?? null : null; },
                    get placeLabel() { return this.commonArea ? this.commonAreaLabel : (this.roomState === 'valid' ? 'Chambre ' + this.roomNumber : null); },
                    get occupancyLabel() { return this.occupancies.find((o) => o.value === this.occupancy)?.label ?? null; },
                    get categoryLabel() { return this.categories.find((c) => c.value === this.category)?.label ?? null; },

                    press(d) {
                        if (this.roomNumber.length >= 5) return;
                        this.roomNumber += d;
                        this.occupancy = null;
                    },
                    // Clavier physique (ordinateur) à l'étape 1 : chiffres et effacement.
                    typeKey(e) {
                        if (this.step !== 1 || this.commonArea || this.scanning || e.target?.closest?.('textarea, input')) return;
                        if (/^[0-9]$/.test(e.key)) { this.press(e.key); e.preventDefault(); }
                        else if (e.key === 'Backspace') { this.roomNumber = this.roomNumber.slice(0, -1); e.preventDefault(); }
                        else if (e.key === 'Enter' && this.stepValid) { this.next(); e.preventDefault(); }
                    },

                    async openScanner() {
                        this.scanError = '';
                        this.scanning = true;
                        try {
                            const { startScan, roomFromCode } = await window.loadQrScanner();
                            await this.$nextTick();
                            stopScan = await startScan(this.$refs.video, (text) => {
                                stopScan = null;
                                this.scanning = false;
                                const room = roomFromCode(text);
                                if (room) {
                                    this.commonArea = false;
                                    this.roomNumber = room;
                                    this.occupancy = null;
                                    navigator.vibrate?.(60);
                                } else {
                                    this.error = 'Ce QR code n\'est pas celui d\'une chambre.';
                                }
                            });
                        } catch (e) {
                            this.scanError = 'La caméra est bloquée ou absente. Autorisez la caméra, ou tapez le numéro.';
                        }
                    },
                    closeScanner() {
                        stopScan?.();
                        stopScan = null;
                        this.scanning = false;
                    },

                    // ----- Étapes -----
                    get stepValid() {
                        return [
                            this.commonArea || (this.roomState === 'valid' && !!this.occupancy),
                            !!this.category,
                            this.recState !== 'recording',
                            this.canSend,
                        ][this.step - 1];
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
                    get canSend() {
                        return (this.commonArea || (this.roomState === 'valid' && !!this.occupancy))
                            && !!this.category && !this.sending && this.recState !== 'recording';
                    },
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
                        if (this.recState === 'recording') this.stopRecord();
                        history.go(n - this.step);
                    },

                    // ----- Message vocal -----
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

                        // Forme d'onde : niveau sonore mesuré 10 fois par seconde.
                        levels = [];
                        try {
                            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            const analyser = audioCtx.createAnalyser();
                            analyser.fftSize = 1024;
                            audioCtx.createMediaStreamSource(stream).connect(analyser);
                            const buffer = new Uint8Array(analyser.fftSize);
                            sampler = setInterval(() => {
                                analyser.getByteTimeDomainData(buffer);
                                let sum = 0;
                                for (const v of buffer) sum += ((v - 128) / 128) ** 2;
                                levels.push(Math.min(1, Math.sqrt(sum / buffer.length) * 5));
                                const recent = levels.slice(-BARS);
                                this.bars = Array(BARS - recent.length).fill(0.1).concat(recent);
                            }, 100);
                        } catch (e) { audioCtx = null; }

                        recorder.start();
                        this.seconds = 0;
                        this.playProgress = 0;
                        this.recState = 'recording';
                        navigator.vibrate?.(60);
                        timer = setInterval(() => { if (++this.seconds >= this.maxSeconds) this.stopRecord(); }, 1000);
                    },

                    stopRecord() {
                        clearInterval(timer);
                        clearInterval(sampler);
                        audioCtx?.close().catch(() => {});
                        audioCtx = null;
                        this.bars = downsample(levels);
                        if (recorder && recorder.state !== 'inactive') recorder.stop();
                        navigator.vibrate?.(60);
                    },

                    togglePlay() {
                        const player = this.$refs.player;
                        if (!player) return;
                        player.paused ? player.play() : player.pause();
                    },

                    resetAudio() {
                        this.$refs.player?.pause();
                        if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                        audioBlob = null;
                        this.audioUrl = null;
                        this.seconds = 0;
                        this.playProgress = 0;
                        this.bars = flat();
                        this.recState = 'idle';
                    },

                    // ----- Photo -----
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

                    // ----- Envoi -----
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
                                setTimeout(() => { window.location.replace(json.redirect); }, 300);
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
                            // Pas de réseau : le signalement est gardé dans le téléphone et
                            // repartira tout seul (bandeau « en attente d'envoi » de la coquille HK).
                            if (await this.keepForLater(data)) return;
                            this.error = 'Pas de réseau. Réessayez quand le wifi revient.';
                        }
                        this.sending = false;
                    },

                    async keepForLater(data) {
                        if (!window.hkOutbox?.supported) return false;
                        const fields = {};
                        for (const [name, value] of data.entries()) {
                            if (typeof value === 'string' && name !== '_token') fields[name] = value;
                        }
                        try {
                            await window.hkOutbox.save({
                                action: this.$root.action,
                                label: [this.placeLabel, this.categoryLabel].filter(Boolean).join(' · '),
                                fields,
                                audio: audioBlob,
                                audioName: audioBlob ? 'message-vocal.' + extensionFor(audioBlob.type) : null,
                                photo: photoBlob,
                            });
                        } catch (e) {
                            return false;
                        }
                        // Hors ligne : on reste sur la page (la quitter échouerait) et on rassure.
                        this.queued = true;
                        this.sending = false;
                        window.dispatchEvent(new Event('hk-outbox-changed'));
                        return true;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
