@php
    $activeSession = $workOrder->activeSession();
@endphp

<div class="bg-white rounded-xl border border-line">
    <div class="px-5 py-4 border-b border-line-soft flex items-center justify-between">
        <h3 class="font-semibold text-navy-900 text-sm">Suivi de l'intervention</h3>
        <span class="text-xs text-ink-grey">
            Temps total : <strong class="text-slate-700">{{ $workOrder->total_worked_minutes }} min</strong>
        </span>
    </div>

    <div class="p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                @if ($activeSession)
                    <p class="text-sm text-ink-grey">Intervention en cours depuis :</p>
                    <p class="text-2xl font-mono font-bold text-emerald-600" id="timer" data-started-at="{{ $activeSession->started_at->toIso8601String() }}">
                        00:00:00
                    </p>
                @else
                    <p class="text-sm text-ink-grey">Aucune intervention en cours.</p>
                @endif
            </div>

            <div>
                @if ($activeSession)
                    <form method="POST" action="{{ route('work-orders.sessions.stop', $workOrder) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                            ⏸ Arrêter
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700">
                            ▶ Démarrer
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if ($workOrder->interventionSessions->isNotEmpty())
            <div class="border-t border-line-soft pt-3 space-y-2">
                @foreach ($workOrder->interventionSessions as $session)
                    <div class="text-xs text-ink-grey flex justify-between">
                        <span>
                            {{ $session->technician->name }} —
                            {{ $session->started_at->format('d/m/Y H:i') }}
                            @if ($session->ended_at)
                                → {{ $session->ended_at->format('H:i') }}
                            @else
                                <span class="text-emerald-600 font-medium">(en cours)</span>
                            @endif
                        </span>
                        @if ($session->duration_minutes)
                            <span>{{ $session->duration_minutes }} min</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const timerEl = document.getElementById('timer');
            if (! timerEl) return;

            const startedAt = new Date(timerEl.dataset.startedAt);

            function updateTimer() {
                const diffSeconds = Math.floor((new Date() - startedAt) / 1000);
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
