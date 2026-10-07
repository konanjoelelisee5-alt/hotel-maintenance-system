{{-- Fil « Activité » de la feuille (App\Support\RoomActivity::feed) : avatar, verbe, chambre,
     bulle de description ou carte de fichier. $activity. --}}
@php
    $toneDot = ['red' => 'bg-red', 'green' => 'bg-green', 'blue' => 'bg-blue', 'amber' => 'bg-amber'];
@endphp
<section aria-labelledby="activity-title">
    <div class="flex items-center gap-4">
        <span class="flex-1 h-px bg-line"></span>
        <h2 id="activity-title" class="m-0 text-[15px] font-semibold">Activité</h2>
        <span class="flex-1 h-px bg-line"></span>
    </div>
    <div class="mt-4 flex flex-col gap-5 px-1">
        @forelse ($activity as $a)
            <div class="flex gap-3">
                <span class="relative flex-shrink-0 self-start w-10 h-10">
                    <span class="w-10 h-10 rounded-full bg-paper flex items-center justify-center text-[12.5px] font-bold">{{ $a['who']?->initialsOrGenerated() ?? '?' }}</span>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-white {{ $toneDot[$a['tone']] }}"></span>
                </span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-[14.5px] font-semibold truncate">{{ $a['who']?->name ?? 'Quelqu\'un' }}</span>
                        <span class="text-[12.5px] text-ink-grey whitespace-nowrap tabular">{{ $a['at']->isToday() ? $a['at']->format('H\hi') : $a['at']->locale('fr')->isoFormat('D MMM') }}</span>
                    </div>
                    <div class="text-[13px] text-ink-muted">
                        {{ $a['verb'] }}
                        @if ($a['room'])
                            · <a href="{{ route('reception.dashboard', ['chambre' => $a['room']->number]) }}" class="font-semibold text-blue hover:underline">{{ $a['room']->label }}</a>
                        @endif
                    </div>
                    @if ($a['quote'])
                        <p class="m-0 mt-2.5 px-4 py-3 rounded-[18px] rounded-tl-md bg-info-bg text-[13.5px] leading-snug text-ink-deep">{{ $a['quote'] }}</p>
                    @endif
                    @if ($a['file'])
                        <div class="mt-2.5 flex items-center gap-3 px-3.5 py-3 rounded-[18px] bg-info-bg">
                            <span class="w-9 h-9 rounded-full bg-ink-deep text-white flex items-center justify-center flex-shrink-0"><x-nav-icon :name="$a['file']['kind']" class="w-4 h-4" /></span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[13.5px] font-semibold truncate">{{ $a['file']['name'] }}</span>
                                <span class="block text-[12px] text-ink-muted">{{ $a['file']['size'] }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="m-0 py-4 text-center text-[13.5px] text-ink-grey">Rien de nouveau pour l'instant.</p>
        @endforelse
    </div>
</section>
