{{-- Inspections des chambres (gouvernante) : en lancer une, reprendre celles en cours,
     les chambres à inspecter en priorité, les inspections récentes.
     Données : RoomInspectionController::index(). --}}
<x-app-layout crumb="Outils de la gouvernante" page-title="Inspections">
    <x-slot:primaryAction>
        <a href="{{ route('housekeeping.floor-plan') }}" class="btn btn-secondary"><x-nav-icon name="pin" /> Plan des étages</a>
    </x-slot:primaryAction>

    <div class="grid gap-5 split:grid-cols-[minmax(0,1fr)_340px] items-start">
        <div class="flex flex-col gap-5 min-w-0">
            {{-- Nouvelle inspection --}}
            <section class="bg-white border border-line rounded-xl overflow-hidden">
                <header class="flex items-center gap-3 px-5 py-3.5 border-b border-line-soft">
                    <span class="w-8 h-8 rounded-[8px] border border-line bg-paper text-gold flex items-center justify-center flex-shrink-0"><x-hk.icon name="check-circle" :size="16" /></span>
                    <div class="min-w-0">
                        <h2 class="m-0 text-[14.5px] font-semibold text-navy">Nouvelle inspection</h2>
                        <p class="m-0 text-[12.5px] text-[#6C6658]">{{ count(\App\Support\RoomInspectionChecklist::POINTS) }} points à vérifier : entrée, chambre, salle de bain.</p>
                    </div>
                </header>
                <form method="POST" action="{{ route('inspections.store') }}" class="ui-form flex flex-col min-[420px]:flex-row gap-2.5 px-5 py-4">
                    @csrf
                    <div class="flex-1 min-w-0">
                        <label for="room_number" class="sr-only">Numéro de chambre</label>
                        <input id="room_number" name="room_number" type="text" inputmode="numeric" list="room-numbers" autocomplete="off"
                               value="{{ old('room_number', $prefill) }}" placeholder="Numéro de chambre" required
                               @if ($errors->has('room_number')) aria-invalid="true" @endif class="!font-mono !text-[16px]">
                        <datalist id="room-numbers">
                            @foreach ($roomNumbers as $number)<option value="{{ $number }}">@endforeach
                        </datalist>
                        <x-input-error class="mt-1.5" :messages="$errors->get('room_number')" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg !h-[44px]"><x-hk.icon name="arrow-right" :size="16" /> Commencer</button>
                </form>
            </section>

            {{-- En cours --}}
            @if ($inProgress->isNotEmpty())
                <section class="bg-white border border-amber/40 rounded-xl overflow-hidden">
                    <header class="px-5 py-3 border-b border-line-soft bg-[#FBF1DF] text-[13px] font-semibold text-[#7A5A16]">Inspections en cours · {{ $inProgress->count() }}</header>
                    @foreach ($inProgress as $inspection)
                        @php $p = $inspection->progress(); @endphp
                        <a href="{{ route('inspections.show', $inspection) }}" class="flex items-center gap-3 px-5 py-3 border-b border-line-soft last:border-b-0 hover:bg-paper">
                            <span class="flex-1 min-w-0">
                                <span class="block text-[14px] font-semibold text-navy">{{ $inspection->room->label }}</span>
                                <span class="block text-[12px] text-[#6C6658]">{{ $inspection->inspector->name }} · commencée {{ $inspection->created_at->locale('fr')->diffForHumans() }}</span>
                            </span>
                            <span class="font-mono text-[12px] text-[#4A4639]">{{ $p['answered'] }}/{{ $p['total'] }}</span>
                            <span class="btn btn-sm btn-secondary">Reprendre</span>
                        </a>
                    @endforeach
                </section>
            @endif

            {{-- Récentes --}}
            <section class="bg-white border border-line rounded-xl overflow-hidden">
                <header class="px-5 py-3.5 border-b border-line-soft">
                    <h2 class="m-0 text-[15px] font-semibold text-navy">Inspections récentes</h2>
                </header>
                @forelse ($recent as $inspection)
                    @php $score = $inspection->conformity(); $nok = $inspection->items->where('result', 'nok')->count(); @endphp
                    <a href="{{ route('inspections.show', $inspection) }}" class="flex items-center gap-3 px-5 py-3 border-b border-line-soft last:border-b-0 hover:bg-paper">
                        <span class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0 font-mono text-[12px] font-semibold
                                     {{ $score === null ? 'bg-line-soft text-ink-grey' : ($score === 100 ? 'bg-[#E6F3EC] text-green' : ($score >= 80 ? 'bg-[#FBF1DF] text-[#7A5A16]' : 'bg-[#FDECEA] text-red')) }}">
                            {{ $score !== null ? $score.'%' : '—' }}
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-[14px] font-semibold text-navy">{{ $inspection->room->label }}</span>
                            <span class="block text-[12px] text-[#6C6658] truncate">{{ $inspection->completed_at->locale('fr')->translatedFormat('j F à H\hi') }} · {{ $inspection->inspector->name }}</span>
                        </span>
                        <span class="text-[12px] whitespace-nowrap {{ $nok ? 'text-red font-semibold' : 'text-green' }}">{{ $nok ? $nok.' non conforme(s)' : 'Tout conforme' }}</span>
                    </a>
                @empty
                    <p class="m-0 px-5 py-8 text-center text-[13px] text-ink-grey">Aucune inspection pour l'instant.</p>
                @endforelse
            </section>
        </div>

        {{-- À inspecter en priorité --}}
        <section class="bg-white border border-line rounded-xl px-5 py-4">
            <h2 class="m-0 text-[15px] font-semibold text-navy">À inspecter en priorité</h2>
            <p class="m-0 mt-0.5 mb-3 text-[12.5px] text-ink-grey">Jamais inspectées, ou pas depuis {{ \App\Support\RoomInspectionChecklist::DUE_AFTER_DAYS }} jours.</p>
            <div class="flex flex-col">
                @forelse ($due as $d)
                    <form method="POST" action="{{ route('inspections.store') }}" class="border-b border-line-soft last:border-b-0">
                        @csrf
                        <input type="hidden" name="room_number" value="{{ $d['room']->number }}">
                        <button type="submit" class="w-full flex items-center gap-3 py-2.5 text-left group">
                            <span class="font-mono text-[14px] font-semibold text-navy w-14">{{ $d['room']->number }}</span>
                            <span class="flex-1 text-[12.5px] {{ $d['last'] ? 'text-[#6C6658]' : 'text-[#7A5A16]' }}">{{ $d['last'] ? 'Inspectée '.$d['last']->locale('fr')->diffForHumans() : 'Jamais inspectée' }}</span>
                            <span class="text-[12.5px] font-semibold text-navy group-hover:underline">Inspecter</span>
                        </button>
                    </form>
                @empty
                    <p class="m-0 text-[13px] text-green">Toutes les chambres ont été inspectées récemment.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
