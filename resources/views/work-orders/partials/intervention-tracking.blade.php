{{-- Temps de l'intervention. Intervenant assigné : chrono, puis ses temps, corrigeables
     (chrono oublié, horaires faux) et saisie après coup. $readonly (superviseur) : la
     liste seule, avec les temps saisis ou corrigés à la main signalés. --}}
@php
    $readonly = $readonly ?? false;
    $activeSession = $workOrder->activeSession();
    $sessions = $workOrder->interventionSessions->sortByDesc('started_at');
    $mine = fn ($session) => ! $readonly && $session->technician_id === auth()->id();
    $local = fn ($date) => $date?->format('Y-m-d\TH:i');
    // Formulaire à rouvrir après une erreur de saisie : 'new' ou l'id de la session corrigée.
    $openForm = $errors->hasAny(['started_at', 'ended_at']) ? old('_session', 'new') : null;
    $openForm = is_numeric($openForm) ? (int) $openForm : $openForm;
@endphp

<x-panel title="Suivi de l'intervention" icon="clock" flush>
    <x-slot:badge>
        <span class="text-[12px] text-ink-muted">Total : <strong class="text-navy">{{ \App\Support\Duration::human($workOrder->total_worked_minutes) }}</strong></span>
    </x-slot:badge>

    <div x-data="{ form: @js($openForm) }">
        @unless ($readonly)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 py-5 {{ $activeSession ? 'bg-ok-bg/50' : '' }}">
                <div>
                    @if ($activeSession)
                        <p class="m-0 flex items-center gap-1.5 text-[12px] font-semibold uppercase tracking-wide text-green">
                            <span class="w-2 h-2 rounded-full bg-green animate-pulse"></span> Intervention en cours depuis {{ $activeSession->started_at->format('H\hi') }}
                        </p>
                        <p class="m-0 mt-1 font-mono text-[34px] leading-none font-semibold text-navy" id="timer" data-started-at="{{ $activeSession->started_at->toIso8601String() }}">00:00:00</p>
                    @else
                        <p class="m-0 text-[12px] font-semibold uppercase tracking-wide text-ink-muted">Chrono arrêté</p>
                        <p class="m-0 mt-1 text-[13.5px] text-ink-body">Démarrez le chrono en arrivant sur place, arrêtez-le en partant.</p>
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
        @endunless

        @if ($sessions->isNotEmpty())
            <ul class="m-0 p-0 list-none border-t border-line-soft divide-y divide-line-soft">
                @foreach ($sessions as $session)
                    <li class="px-5 py-2.5 text-[12.5px]">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-ink-body min-w-0">
                                <span class="font-semibold text-navy">{{ $session->technician->name }}</span>
                                · {{ $session->started_at->format('d/m H\hi') }}
                                @if ($session->ended_at) → {{ $session->ended_at->format('H\hi') }} @endif
                                @if ($session->is_manual)
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-warn-bg text-warn-ink text-[11px] font-semibold">saisi à la main</span>
                                @elseif ($session->corrected_at)
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-warn-bg text-warn-ink text-[11px] font-semibold">corrigé</span>
                                @endif
                            </span>
                            <span class="flex items-center gap-2 flex-shrink-0">
                                @if ($session->ended_at)
                                    <span class="font-mono text-ink-muted">{{ \App\Support\Duration::human($session->duration_minutes) }}</span>
                                @else
                                    <span class="text-green font-semibold">en cours</span>
                                @endif
                                @if ($mine($session))
                                    <button type="button" class="btn btn-sm btn-ghost" @click="form = form === {{ $session->id }} ? null : {{ $session->id }}"
                                            :aria-expanded="(form === {{ $session->id }}).toString()">Corriger</button>
                                @endif
                            </span>
                        </div>
                        @if ($mine($session))
                            <form method="POST" action="{{ route('work-orders.sessions.update', [$workOrder, $session]) }}" x-show="form === {{ $session->id }}" x-cloak
                                  class="mt-2.5 grid gap-2.5 sm:grid-cols-[1fr_1fr_auto] items-end p-3 rounded-[10px] bg-paper border border-line">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_session" value="{{ $session->id }}">
                                @include('work-orders.partials.session-fields', ['start' => $local($session->started_at), 'end' => $local($session->ended_at ?? now())])
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif ($readonly)
            <p class="m-0 px-5 py-4 text-[13px] text-ink-muted">Aucun temps saisi pour l'instant.</p>
        @endif

        @unless ($readonly)
            {{-- Chrono oublié : la période se saisit après coup, signalée au responsable. --}}
            <div class="px-5 py-3 border-t border-line-soft">
                <button type="button" class="btn btn-sm btn-ghost -ml-2" @click="form = form === 'new' ? null : 'new'" :aria-expanded="(form === 'new').toString()">
                    <x-nav-icon name="clock" /> Saisir un temps oublié
                </button>
                <form method="POST" action="{{ route('work-orders.sessions.store', $workOrder) }}" x-show="form === 'new'" x-cloak
                      class="mt-2 grid gap-2.5 sm:grid-cols-[1fr_1fr_auto] items-end p-3 rounded-[10px] bg-paper border border-line">
                    @csrf
                    <input type="hidden" name="_session" value="new">
                    @include('work-orders.partials.session-fields', ['start' => old('_session') === 'new' ? old('started_at') : null, 'end' => old('_session') === 'new' ? old('ended_at') : null])
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        @endunless

        <div class="px-5 pb-3">
            <x-input-error :messages="$errors->get('started_at')" />
            <x-input-error :messages="$errors->get('ended_at')" />
        </div>
    </div>
</x-panel>

@unless ($readonly)
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
@endunless
