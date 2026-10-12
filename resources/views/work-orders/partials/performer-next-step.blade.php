{{-- Carte « À vous » de l'intervenant assigné (technicien) : la prochaine étape et son
     unique bouton, selon l'état de l'OT. Pendant du panneau « Pilotage » du superviseur,
     au même endroit et du même dessin. Elle parle comme l'assistant en 4 étapes (mission) :
     « étape 2 sur 4 », et ses boutons ouvrent l'étape ($dispatch('mission-step', n)). --}}
@php
    $status = $workOrder->status;
    $session = $workOrder->activeSession();
    $lastHold = $status === 'en_attente'
        ? $workOrder->statusHistories->where('new_status', 'en_attente')->sortByDesc('id')->first()
        : null;
    // Même calcul que mission.blade.php.
    $step = match (true) {
        $status === 'resolu' => 4,
        $status === 'ouvert' && $workOrder->interventionSessions->isEmpty() => 1,
        default => 2,
    };
    $new = $workOrder->awaitsAcknowledgement();
@endphp

<section class="flex flex-col sm:flex-row sm:items-center gap-3 justify-between p-4 rounded-xl border {{ $new ? 'border-gold/50 bg-warn-bg' : 'border-gold/30 bg-[#FBF6EC]' }}">
    <div class="flex gap-3 min-w-0">
        <span class="w-9 h-9 flex-shrink-0 rounded-full bg-white border border-gold/30 flex items-center justify-center text-gold">
            <x-nav-icon :name="$new ? 'bell' : 'wrench'" class="w-[18px] h-[18px]" />
        </span>
        <div class="text-[13.5px] leading-snug text-ink-strong min-w-0">
            <div class="text-[11px] font-semibold text-[#7D7768] uppercase tracking-wide mb-0.5">À vous · étape {{ $step }} sur 4</div>
            @switch(true)
                @case($new)
                    <p class="m-0 text-[14.5px] font-semibold text-ink-deep">Nouvel ordre pour vous</p>
                    Dites au manager que vous l'avez vu. Démarrer le chrono le fait aussi.
                    @break
                @case($status === 'ouvert')
                    Préparez vos pièces, puis sur place <strong>démarrez le chrono</strong> : l'OT passe en réparation.
                    @break
                @case($status === 'en_cours' && $session !== null)
                    Chrono en marche depuis {{ $session->started_at->format('H\hi') }}. Réparation finie ?
                    <strong>Terminez</strong> : rapport et signature, l'OT passe en « Réparé ».
                    @break
                @case($status === 'en_cours')
                    Réparation finie ? <strong>Terminez</strong> : rapport et signature, l'OT passe en « Réparé ».
                    @break
                @case($status === 'en_attente')
                    En attente{{ $lastHold?->note ? ' — « '.$lastHold->note.' »' : '' }}.
                    Le blocage est levé ? <strong>Reprenez la réparation</strong>.
                    @break
                @case($status === 'rejete')
                    Le contrôle qualité demande une <strong>correction</strong>. Lisez ce qui est demandé, puis reprenez.
                    @break
                @default
                    Réparation envoyée au contrôle qualité. Rien à faire pour l'instant.
            @endswitch
        </div>
    </div>

    <div class="flex-shrink-0 flex items-center gap-2">
        @if ($new)
            <form method="POST" action="{{ route('work-orders.acknowledge', $workOrder) }}" class="flex-1 sm:flex-none">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg w-full"><x-nav-icon name="check" /> J'ai vu, je m'en occupe</button>
            </form>
        @elseif ($status === 'ouvert' && ! $session)
            <form method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}" class="flex-1 sm:flex-none">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg w-full"><x-nav-icon name="play" /> Démarrer le chrono</button>
            </form>
        @elseif ($status === 'en_cours')
            <button type="button" class="btn btn-primary btn-lg flex-1 sm:flex-none" x-on:click="$dispatch('mission-step', 2)"><x-nav-icon name="report" /> Terminer</button>
        @elseif ($status === 'en_attente')
            <button type="button" class="btn btn-primary btn-lg flex-1 sm:flex-none" x-on:click="$dispatch('mission-step', 2)"><x-nav-icon name="play" /> Reprendre</button>
        @elseif ($status === 'rejete')
            <button type="button" class="btn btn-primary btn-lg flex-1 sm:flex-none" x-on:click="$dispatch('mission-step', 2)"><x-nav-icon name="shield" /> Voir la correction</button>
        @endif
    </div>
</section>
