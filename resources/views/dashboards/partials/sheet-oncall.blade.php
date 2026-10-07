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
            @if ($lead->phone)
                @php $tel = preg_replace('/[^\d+]/', '', $lead->phone); @endphp
                <a href="tel:{{ $tel }}" class="w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-[0_8px_18px_-12px_rgba(23,25,31,.5)] hover:-translate-y-0.5 transition-transform" title="Appeler {{ $lead->name }}" aria-label="Appeler {{ $lead->name }}">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                </a>
                <a href="sms:{{ $tel }}" class="w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-[0_8px_18px_-12px_rgba(23,25,31,.5)] hover:-translate-y-0.5 transition-transform" title="Envoyer un SMS" aria-label="Envoyer un SMS à {{ $lead->name }}">
                    <x-nav-icon name="comment" class="w-5 h-5" />
                </a>
            @else
                <span class="text-[13px] text-ink-muted">Pas de téléphone renseigné</span>
            @endif
        </div>
        @if ($lead->phone)<div class="mt-3 text-[13px] font-semibold tabular text-ink-body">{{ $lead->phone }}</div>@endif
        @foreach ($onCall['people']->skip(1) as $person)
            <div class="mt-4 w-full flex items-center gap-3 px-3 py-2.5 rounded-2xl bg-white text-left">
                <span class="w-9 h-9 rounded-full bg-paper flex items-center justify-center text-[12px] font-bold">{{ $person->initialsOrGenerated() }}</span>
                <span class="flex-1 min-w-0 text-[13.5px] font-semibold truncate">{{ $person->name }}</span>
                @if ($person->phone)
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $person->phone) }}" class="btn btn-sm btn-secondary">Appeler</a>
                @endif
            </div>
        @endforeach
    @else
        <div class="text-[15px] font-bold">{{ $onCall['label'] }}</div>
        <p class="m-0 mt-2 text-[13.5px] text-red font-medium">Personne n'est d'astreinte en ce moment : prévenez l'administrateur.</p>
    @endif
</section>
