{{-- Accueil de la réception (DashboardController::reception), dans layouts.sheet :
     - au centre : bonjour + date, recherche d'une chambre (et sa fiche), trois repères, la
       courbe des pannes signalées sur 7 jours, les clients concernés, les réparations à confirmer ;
     - à droite : l'astreinte à appeler, ce qui vient de se passer, les blocages à décider.
     Ordinateur du comptoir d'abord, téléphone ensuite. --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $desk = \App\Support\ReceptionDesk::class;
    $occ = \App\Enums\RoomOccupancy::class;
    $category = fn ($w) => ($c = $hk::category($w)) ? $hk::categoryLabel($c) : $w->title;
    $roleLabel = fn ($u) => $u?->role?->label() ?? '—';
    $firstName = \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');


    // Clients concernés : couleur de la pastille et de l'icône selon la situation.
    $situation = fn ($occupancy) => match ($occupancy) {
        $occ::ClientPresent => ['dot' => 'bg-red', 'icon' => 'user', 'circle' => 'bg-[#FDEBDD] text-[#B4561A]'],
        $occ::ClientAbsent => ['dot' => 'bg-[rgb(var(--rc-orange))]', 'icon' => 'clock', 'circle' => 'bg-[rgb(var(--rc-sky))] text-[rgb(var(--rc-sky-ink))]'],
        default => ['dot' => 'bg-blue', 'icon' => 'calendar', 'circle' => 'bg-paper text-ink-deep'],
    };
@endphp

<x-app-layout page-title="Accueil">
    <x-slot:greeting>
        <div class="flex flex-wrap items-start gap-x-6 gap-y-4">
            <div class="flex-1 min-w-[240px]">
                <h1 class="m-0 text-[28px] tab:text-[32px] font-bold tracking-[-0.025em] leading-tight">Bonjour, {{ $firstName }}</h1>
                <p class="m-0 mt-2 text-[15px] text-ink-muted">
                    <span class="font-semibold text-ink-body first-letter:uppercase inline-block">{{ now()->locale('fr')->isoFormat('dddd D MMMM') }}</span> ·
                    @if ($guestRooms->isEmpty())
                        Aucun client n'attend de réparation en ce moment.
                    @else
                        {{ $guestRooms->count() }} {{ $guestRooms->count() > 1 ? 'chambres occupées attendent' : 'chambre occupée attend' }} une réparation.
                    @endif
                </p>
            </div>
        </div>
    </x-slot:greeting>

    <x-slot:primaryAction>
        <a href="{{ route('quick-reports.create') }}" class="btn btn-gold"><x-nav-icon name="mic" /> Signaler une panne</a>
    </x-slot:primaryAction>

    {{-- Recherche d'une chambre --}}
    <form method="GET" action="{{ route('reception.dashboard') }}" role="search" class="flex flex-col sm:flex-row gap-2.5">
        <label for="chambre" class="sr-only">Numéro de chambre</label>
        <div class="relative flex-1 min-w-0">
            <x-nav-icon name="search" class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-ink-grey pointer-events-none" />
            <input id="chambre" name="chambre" type="search" inputmode="numeric" list="room-numbers" autocomplete="off" value="{{ $searched }}"
                   placeholder="Chercher une chambre, ex. 305" @if (! $searched) autofocus @endif
                   class="w-full h-14 pl-14 pr-5 rounded-full border-0 bg-paper text-[17px] font-semibold tabular placeholder:font-medium placeholder:text-[15px] placeholder:text-ink-grey
                          focus:bg-white focus:ring-2 focus:ring-blue/40 transition">
            <datalist id="room-numbers">@foreach ($roomNumbers as $n)<option value="{{ $n }}">@endforeach</datalist>
        </div>
        <button type="submit" class="btn btn-primary btn-lg !h-14 !rounded-full !px-7">Voir la chambre</button>
    </form>
    @if ($searched !== '' && ! $lookup)
        <p class="m-0 -mt-3 px-5 text-[13.5px] font-medium text-red">Aucune chambre « {{ $searched }} ». Vérifiez le numéro.</p>
    @endif

    {{-- Fiche de la chambre recherchée --}}
    @if ($lookup)
        @php
            $room = $lookup['room'];
            $open = $lookup['open'];
        @endphp
        <section class="rounded-[24px] border border-line overflow-hidden" aria-label="Chambre {{ $room->number }}">
            <header class="flex flex-wrap items-center gap-3 px-5 tab:px-6 py-5">
                <span class="w-12 h-12 rounded-full bg-paper flex items-center justify-center flex-shrink-0">
                    <x-nav-icon name="building" class="w-5 h-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="m-0 text-[20px] font-bold tracking-[-0.015em]">{{ $room->label }}</h2>
                    <p class="m-0 text-[13.5px] text-ink-muted">{{ $room->floor ?: 'Étage non renseigné' }} · {{ $room->status_label }}</p>
                </div>
                @if ($lookup['block'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[12.5px] font-semibold {{ $lookup['block']->status === \App\Models\RoomBlock::BLOCKED ? 'bg-ink-deep text-white' : 'bg-warn-bg text-warn-ink' }}">
                        <x-nav-icon name="ban" class="w-3.5 h-3.5" /> {{ $lookup['block']->status_label }}
                    </span>
                @endif
                <a href="{{ route('quick-reports.create', ['chambre' => $room->number]) }}" class="btn btn-secondary"><x-nav-icon name="mic" /> Signaler une panne ici</a>
            </header>

            {{-- À dire au client : la bulle de la maquette --}}
            <div class="mx-5 tab:mx-6 mb-5 px-5 py-4 rounded-[20px] rounded-tl-md bg-info-bg">
                <div class="text-[12.5px] font-bold text-[rgb(var(--rc-sky-ink))] mb-1">À dire au client</div>
                @forelse ($open as $w)
                    <p class="m-0 text-[15px] leading-relaxed text-ink-deep"><strong>{{ $category($w) }} :</strong> {{ $desk::answerFor($w) }}</p>
                @empty
                    <p class="m-0 text-[15px] text-ink-deep">Aucune panne en cours dans cette chambre.</p>
                @endforelse
                @if ($lookup['deadline'])
                    <p class="m-0 mt-1.5 text-[13.5px] text-[rgb(var(--rc-sky-ink))]">Réparation attendue avant <strong>{{ $lookup['deadline']->format('H\hi') }}</strong>{{ $lookup['deadline']->isToday() ? '' : ' (le '.$lookup['deadline']->format('d/m').')' }}.</p>
                @endif
            </div>

            <div class="grid split:grid-cols-[minmax(0,1fr)_340px] border-t border-line">
                {{-- Pannes en cours (tous services, lecture seule) --}}
                <div class="px-5 tab:px-6 py-5 min-w-0 split:border-r border-line">
                    <h3 class="m-0 mb-2 text-[15px] font-bold">Pannes en cours · {{ $open->count() }}</h3>
                    @forelse ($open as $w)
                        <div class="py-3 border-b border-line-soft last:border-b-0 flex flex-col gap-1.5">
                            <div class="flex items-start justify-between gap-3">
                                <span class="text-[14.5px] font-semibold">{{ $category($w) }}</span>
                                @can('view', $w)
                                    <a href="{{ route('work-orders.show', $w) }}" class="text-[12.5px] font-semibold text-blue hover:underline tabular">{{ $w->code() }}</a>
                                @else
                                    <span class="text-[12.5px] text-ink-grey tabular">{{ $w->code() }}</span>
                                @endcan
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-work-order-status-badge :status="$w->status" />
                                @if ($w->priority?->code === 'urgente')<x-work-order-priority-badge :priority="$w->priority" />@endif
                                @if ($w->slaSummary()['late'])<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red text-white">En retard</span>@endif
                            </div>
                            <p class="m-0 text-[13px] text-ink-muted">
                                {{ $w->assignee ? 'Technicien : '.$w->assignee->name : 'Pas encore de technicien' }}
                                @if ($w->scheduled_at) · passage prévu {{ $w->scheduled_at->format('d/m H\hi') }} @endif
                                · signalé par {{ $w->reporter?->name ?? '—' }} ({{ $roleLabel($w->reporter) }}), {{ $w->created_at->locale('fr')->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <p class="m-0 text-[13.5px] text-ink-grey">Rien à signaler.</p>
                    @endforelse

                    @if ($lookup['recent']->isNotEmpty())
                        <h3 class="m-0 mt-5 mb-1.5 text-[13px] font-bold text-ink-muted">Réparé ces {{ $desk::HISTORY_DAYS }} derniers jours</h3>
                        <ul class="m-0 p-0 list-none">
                            @foreach ($lookup['recent'] as $w)
                                <li class="flex justify-between gap-3 py-1.5 text-[13.5px]">
                                    <span class="truncate">{{ $category($w) }}</span>
                                    <span class="text-[12.5px] text-ink-grey whitespace-nowrap tabular">{{ $w->completed_at->format('d/m H\hi') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Situation du client --}}
                <div class="px-5 tab:px-6 py-5 border-t split:border-t-0 border-line">
                    <h3 class="m-0 text-[15px] font-bold">Situation du client</h3>
                    <p class="m-0 mt-0.5 mb-3.5 text-[13px] text-ink-muted">Actuellement : <strong class="text-ink-deep">{{ $lookup['occupancy']?->label() ?? 'non précisée' }}</strong></p>
                    @if ($open->isEmpty())
                        <p class="m-0 text-[13.5px] text-ink-grey">Pas de panne en cours : rien à transmettre.</p>
                    @else
                        <form method="POST" action="{{ route('reception.situation', $room) }}" class="ui-form flex flex-col gap-3" x-data="{ situation: '{{ old('situation') }}' }">
                            @csrf
                            <div class="flex flex-col gap-1.5" role="radiogroup" aria-label="Situation du client">
                                @foreach ($desk::SITUATIONS as $key => $label)
                                    <label class="!h-auto !py-2.5 !w-full"><input type="radio" name="situation" value="{{ $key }}" x-model="situation"> {{ $label }}</label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('situation')" />
                            <div x-show="situation === 'sorti' || situation === 'arrivee'" x-cloak>
                                <label for="situation-time" x-text="situation === 'sorti' ? 'Retour vers' : 'Arrivée vers'">Heure</label>
                                <input id="situation-time" type="time" name="time" value="{{ old('time') }}">
                                <x-input-error class="mt-1" :messages="$errors->get('time')" />
                            </div>
                            <div x-show="situation === 'reloge'" x-cloak>
                                <label for="other-room">Nouvelle chambre (facultatif)</label>
                                <input id="other-room" type="text" name="other_room" maxlength="20" value="{{ old('other_room') }}" placeholder="ex. 312">
                            </div>
                            <div>
                                <label for="situation-note">Précision (facultatif)</label>
                                <input id="situation-note" type="text" name="note" maxlength="300" value="{{ old('note') }}" placeholder="ex. client allergique, préfère qu'on intervienne le matin">
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="! situation">Informer la maintenance</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @include('dashboards.partials.sheet-kpis')

    @include('dashboards.partials.sheet-chart', ['chartTitle' => 'Pannes signalées en chambre'])

    {{-- Clients concernés maintenant --}}
    <section aria-labelledby="guests-title" class="pt-2">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <h2 id="guests-title" class="m-0 text-[20px] font-bold tracking-[-0.015em]">Clients concernés maintenant</h2>
            @if ($guestRooms->isNotEmpty())
                <span class="h-5 w-px bg-line" aria-hidden="true"></span>
                <span class="text-[14px] font-semibold text-ink-body">{{ $guestRooms->where('urgent', true)->count() }} urgent{{ $guestRooms->where('urgent', true)->count() > 1 ? 's' : '' }} sur {{ $guestRooms->count() }}</span>
            @endif
            <a href="{{ route('work-orders.index') }}" class="ml-auto h-9 px-4 rounded-full border border-line inline-flex items-center gap-1.5 text-[13px] font-semibold hover:bg-paper transition-colors">
                Toutes les demandes
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        </div>

        <div class="mt-3">
            @forelse ($guestRooms as $g)
                @php
                    $s = $situation($g['occupancy']);
                    $late = $g['deadline'] && $g['deadline']->lt(now()->addHour());
                @endphp
                <a href="{{ route('reception.dashboard', ['chambre' => $g['room']->number]) }}"
                   class="group grid grid-cols-[48px_minmax(0,1fr)_auto] sm:grid-cols-[48px_minmax(0,1.4fr)_minmax(0,1fr)_96px_32px] items-center gap-x-4 gap-y-1 py-3 -mx-3 px-3 rounded-2xl hover:bg-paper transition-colors">
                    <span class="w-12 h-12 rounded-full flex items-center justify-center {{ $s['circle'] }}">
                        <x-nav-icon :name="$s['icon']" class="w-5 h-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold truncate"><span class="tabular">{{ $g['room']->number }}</span> · {{ $g['orders']->map($category)->unique()->join(', ') }}</span>
                        @if ($g['urgent'])<span class="block text-[12.5px] font-semibold text-red">Urgent</span>@endif
                    </span>
                    <span class="hidden sm:flex items-center gap-2 text-[13.5px] font-medium text-ink-body min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $s['dot'] }}"></span>
                        <span class="truncate">{{ $g['occupancy']?->label() ?? 'Arrivée prévue' }}</span>
                    </span>
                    <span class="flex items-center gap-1.5 text-[14px] font-bold tabular justify-self-end sm:justify-self-start {{ $late ? 'text-red' : '' }}">
                        @if ($g['deadline'])
                            <x-nav-icon name="clock" class="w-4 h-4 {{ $late ? 'text-red' : 'text-ink-faint' }}" />
                            {{ $g['deadline']->isToday() ? $g['deadline']->format('H\hi') : $g['deadline']->locale('fr')->isoFormat('D MMM') }}
                        @else
                            <span class="text-ink-faint font-medium">—</span>
                        @endif
                    </span>
                    <span class="hidden sm:flex w-8 h-8 rounded-full items-center justify-center text-ink-grey group-hover:bg-white group-hover:text-ink-deep transition-colors" aria-hidden="true">
                        <x-nav-icon name="more" class="w-4 h-4" />
                    </span>
                    <span class="sm:hidden col-start-2 col-span-2 flex items-center gap-2 text-[12.5px] text-ink-muted">
                        <span class="w-2 h-2 rounded-full {{ $s['dot'] }}"></span>{{ $g['occupancy']?->label() ?? 'Arrivée prévue' }}
                    </span>
                </a>
            @empty
                <p class="m-0 py-10 text-center text-[14px] text-ink-grey">Aucun client concerné par une panne en ce moment.</p>
            @endforelse
        </div>
    </section>

    {{-- Réparations à confirmer (demandes de la réception) --}}
    @if ($toConfirm->isNotEmpty())
        <section aria-labelledby="confirm-title" class="rounded-[24px] bg-paper px-5 tab:px-6 py-5">
            <h2 id="confirm-title" class="m-0 text-[17px] font-bold">À confirmer · {{ $toConfirm->count() }}</h2>
            <p class="m-0 mt-0.5 text-[13.5px] text-ink-muted">Réparations terminées sur vos demandes : prévenez le client, puis confirmez.</p>
            <div class="mt-3 flex flex-col gap-2">
                @foreach ($toConfirm as $w)
                    <a href="{{ route('work-orders.show', $w) }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-white hover:shadow-[0_10px_24px_-16px_rgba(23,25,31,.4)] transition-shadow">
                        <span class="w-9 h-9 rounded-full bg-ok-bg text-green flex items-center justify-center flex-shrink-0"><x-nav-icon name="check" class="w-4 h-4" /></span>
                        <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                            <span class="text-[14.5px] font-semibold truncate">{{ $w->room?->label ?? '—' }} · {{ $w->title }}</span>
                            <span class="text-[12.5px] text-ink-muted">Réparé {{ $w->completed_at?->locale('fr')->diffForHumans() }}{{ $w->assignee ? ' par '.$w->assignee->name : '' }}</span>
                        </span>
                        <span class="btn btn-sm btn-secondary flex-shrink-0">Répondre</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Colonne de droite --}}
    <x-slot:aside>
        @include('dashboards.partials.sheet-oncall')

        @include('dashboards.partials.sheet-activity')

        {{-- Blocages à décider (à la place du champ « message » de la maquette) --}}
        <section class="rounded-[22px] border border-line px-4 py-4" aria-labelledby="blocks-title">
            <div class="flex items-center justify-between gap-3">
                <h2 id="blocks-title" class="m-0 text-[15px] font-bold">Blocages à décider</h2>
                <span class="min-w-[26px] h-[26px] px-2 rounded-full text-[12.5px] font-bold flex items-center justify-center tabular {{ $pendingBlocks->isNotEmpty() ? 'bg-warn-bg text-warn-ink' : 'bg-paper text-ink-grey' }}">{{ $pendingBlocks->count() }}</span>
            </div>
            @forelse ($pendingBlocks->take(3) as $block)
                <div class="mt-3 flex items-center gap-3">
                    <span class="w-9 h-9 rounded-full bg-paper flex items-center justify-center flex-shrink-0"><x-nav-icon name="ban" class="w-4 h-4" /></span>
                    <div class="min-w-0">
                        <div class="text-[13.5px] font-semibold truncate">{{ $block->room?->label }}</div>
                        <div class="text-[12.5px] text-ink-muted truncate">{{ $block->reason }} · {{ $block->requester?->name }}</div>
                    </div>
                </div>
            @empty
                <p class="m-0 mt-2 text-[13px] text-ink-grey">Aucune demande en attente.</p>
            @endforelse
            @if ($pendingBlocks->isNotEmpty())
                <a href="{{ route('room-blocks.index') }}#a-valider" class="btn btn-primary w-full mt-4">Décider</a>
            @endif
        </section>
    </x-slot:aside>

    {{-- Nouveautés pendant une saisie : on ne recharge pas sous les doigts, on propose. --}}
    <div id="reception-news" hidden class="fixed top-3 inset-x-0 z-[70] flex [&[hidden]]:hidden justify-center px-4 pointer-events-none">
        <div class="pointer-events-auto flex items-center gap-3 pl-4 pr-2 py-2 rounded-full bg-navy-dark text-white shadow-[0_16px_36px_-12px_rgba(15,19,30,.6)]">
            <x-nav-icon name="bell" class="w-4 h-4 text-[#8DB8F5]" />
            <span class="text-[13.5px] font-medium">Nouvelles informations</span>
            <button type="button" onclick="location.reload()" class="btn btn-sm btn-gold !rounded-full">Actualiser</button>
        </div>
    </div>

    @push('scripts')
        <script>
            // Écran du comptoir laissé ouvert : toutes les 30 s, on demande l'état de l'accueil
            // (ReceptionController::state). S'il a changé, on recharge — sauf pendant une saisie,
            // où un bandeau propose « Actualiser ». Une nouvelle notification fait aussi sonner.
            (() => {
                const stateUrl = @js(route('reception.state'));
                let signature = @js($signature);
                let unread = @js($unreadNow);
                let typing = false;
                document.addEventListener('input', () => { typing = true; }, true);

                const beep = () => window.hkMedia.beep(2);

                setInterval(async () => {
                    if (document.hidden) return;
                    try {
                        const res = await fetch(stateUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                        if (!res.ok) return;
                        const state = await res.json();
                        if (state.unread > unread) beep();
                        unread = state.unread;
                        if (state.signature === signature) return;
                        // La recherche a le focus par défaut : un champ ne « gêne » que s'il contient une saisie.
                        const field = document.activeElement;
                        const busy = typing || (['INPUT', 'TEXTAREA'].includes(field?.tagName) && field.value !== '' && field.id !== 'chambre');
                        if (busy) {
                            document.getElementById('reception-news').hidden = false;
                        } else {
                            location.reload();
                        }
                    } catch (e) { /* réseau coupé : on réessaiera */ }
                }, 30000);
            })();
        </script>
    @endpush
</x-app-layout>
