{{-- Chrono de l'intervention : affiché uniquement à l'intervenant assigné (cf. show.blade.php). --}}
@php
    $activeSession = $workOrder->activeSession();
@endphp

<x-panel title="Suivi de l'intervention" icon="clock" flush>
    <x-slot:badge>
        <span class="text-[12px] text-ink-grey">Total : <strong class="text-navy">{{ \App\Support\Duration::human($workOrder->total_worked_minutes) }}</strong></span>
    </x-slot:badge>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 py-5 {{ $activeSession ? 'bg-[#E6F3EC]/50' : '' }}">
        <div>
            @if ($activeSession)
                <p class="m-0 flex items-center gap-1.5 text-[12px] font-semibold uppercase tracking-wide text-green">
                    <span class="w-2 h-2 rounded-full bg-green animate-pulse"></span> Intervention en cours depuis {{ $activeSession->started_at->format('H\hi') }}
                </p>
                <p class="m-0 mt-1 font-mono text-[34px] leading-none font-semibold text-navy" id="timer" data-started-at="{{ $activeSession->started_at->toIso8601String() }}">00:00:00</p>
            @else
                <p class="m-0 text-[12px] font-semibold uppercase tracking-wide text-ink-grey">Chrono arrêté</p>
                <p class="m-0 mt-1 text-[13.5px] text-[#4A4639]">Démarrez le chrono en arrivant sur place, arrêtez-le en partant.</p>
            @endif
        </div>

        @if ($activeSession)
            <form method="POST" action="{{ route('work-orders.sessions.stop', $workOrder) }}">
                @csrf
                <button type="submit" class="btn btn-lg btn-danger-solid w-full sm:w-auto"><x-nav-icon name="pause" /> Arrêter le chrono</button>
            </form>
        @else
            <form method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}">
                @csrf
                <button type="submit" class="btn btn-lg btn-primary w-full sm:w-auto"><x-nav-icon name="play" /> Démarrer le chrono</button>
            </form>
        @endif
    </div>

    @if ($workOrder->interventionSessions->isNotEmpty())
        <ul class="m-0 p-0 list-none border-t border-line-soft divide-y divide-line-soft">
            @foreach ($workOrder->interventionSessions as $session)
                <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-[12.5px]">
                    <span class="text-[#4A4639]">
                        <span class="font-semibold text-navy">{{ $session->technician->name }}</span>
                        · {{ $session->started_at->format('d/m H\hi') }}
                        @if ($session->ended_at) → {{ $session->ended_at->format('H\hi') }} @endif
                    </span>
                    @if ($session->ended_at)
                        <span class="font-mono text-[#6C6658]">{{ $session->duration_minutes }} min</span>
                    @else
                        <span class="text-green font-semibold">en cours</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timerEl = document.getElementById('timer');
            if (! timerEl) return;

            const startedAt = new Date(timerEl.dataset.startedAt);

            function updateTimer() {
                const diffSeconds = Math.max(0, Math.floor((new Date() - startedAt) / 1000));
                const hours = String(Math.floor(diffSeconds / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((diffSeconds % 3600) / 60)).padStart(2, '0');
                const seconds = String(diffSeconds % 60).padStart(2, '0');
                timerEl.textContent = `${hours}:${minutes}:${seconds}`;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        });
    </script>
@endpush
