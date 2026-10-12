{{-- Carte « profil » de la maquette : la personne d'astreinte à appeler (ReceptionDesk::onCall). $onCall. --}}
@php $lead = $onCall['people']->first(); @endphp
<section class="rounded-[26px] bg-paper px-5 pt-7 pb-6 flex flex-col items-center text-center" aria-labelledby="oncall-title">
    <h2 id="oncall-title" class="sr-only">{{ $onCall['label'] }}</h2>
    @if ($lead)
        <span class="relative">
            <span class="w-[84px] h-[84px] rounded-full bg-white ring-4 ring-white shadow-[0_12px_24px_-14px_rgba(23,25,31,.45)] flex items-center justify-center text-[26px] font-extrabold text-[rgb(var(--rc-sky-ink))]">{{ $lead->initialsOrGenerated() }}</span>
            <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full bg-green ring-[3px] ring-paper" title="D'astreinte"></span>
        </span>
        <div class="mt-4 text-[17px] font-bold">{{ $lead->name }}</div>
        <div class="mt-0.5 text-[13.5px] text-ink-muted">{{ $onCall['label'] }} · <span class="tabular">{{ $onCall['hours'] }}</span></div>
        <div class="mt-5 flex items-center gap-3">
            @if ($lead->dialablePhone())
                <x-call-contact :person="$lead" />
            @else
                <span class="text-[13px] text-ink-muted">Pas de téléphone renseigné</span>
            @endif
        </div>
        @if ($lead->dialablePhone())<div class="mt-3 text-[13px] font-semibold tabular text-ink-body">{{ $lead->phone }}</div>@endif
        @foreach ($onCall['people']->skip(1) as $person)
            <div class="mt-4 w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-white text-left">
                <span class="w-9 h-9 rounded-full bg-paper flex items-center justify-center text-[12px] font-bold">{{ $person->initialsOrGenerated() }}</span>
                <span class="flex-1 min-w-0 text-[13.5px] font-semibold truncate">{{ $person->name }}</span>
                <x-call-contact :person="$person" variant="button" />
            </div>
        @endforeach
    @else
        <div class="text-[15px] font-bold">{{ $onCall['label'] }}</div>
        <p class="m-0 mt-2 text-[13.5px] text-red font-medium">Personne n'est d'astreinte en ce moment : prévenez l'administrateur.</p>
    @endif
</section>
