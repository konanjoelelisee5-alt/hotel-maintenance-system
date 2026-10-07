<x-app-layout :crumb="'Paramètres'" :page-title="'Astreinte'" :back-route="route('settings.index')">
    <div>
        <div class="w-full max-w-4xl space-y-6">

            {{-- Qui est prévenu en ce moment --}}
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-semibold text-ink-deep">De garde maintenant</h3>
                <p class="text-sm text-ink-muted mt-1">
                    {{ $isDayTime ? 'Journée' : 'Nuit' }} — un nouvel OT urgent ou haut est signalé immédiatement à :
                </p>
                <ul class="mt-3 space-y-1 text-sm">
                    @forelse ($current as $member)
                        <li>
                            <strong>{{ $member->name }}</strong> ({{ $member->role_label }})
                            @if ($member->phone)
                                — <span class="font-mono">{{ $member->phone }}</span>
                            @else
                                — <span class="text-red font-medium">aucun téléphone : alerte dans l'application seulement</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-red font-medium">
                            Personne ! Aucun manager ni admin actif ne reçoit les alertes de maintenance.
                            Cochez « Reçoit les alertes de maintenance » sur au moins un compte.
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Horaires --}}
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-semibold text-ink-deep">Horaires</h3>
                <form method="POST" action="{{ route('on-call.update') }}" class="mt-4 flex flex-wrap items-end gap-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="day_start" value="Début de journée" />
                        <x-text-input id="day_start" name="day_start" type="time" class="mt-1" :value="old('day_start', $dayStart)" required />
                    </div>
                    <div>
                        <x-input-label for="day_end" value="Fin de journée" />
                        <x-text-input id="day_end" name="day_end" type="time" class="mt-1" :value="old('day_end', $dayEnd)" required />
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
                <x-input-error :messages="$errors->get('day_start')" class="mt-2" />
                <x-input-error :messages="$errors->get('day_end')" class="mt-2" />
                <p class="text-xs text-ink-muted mt-3">
                    Tous les jours, week-end compris. Si personne n'est configuré pour une plage, l'alerte part à l'autre équipe.
                    Filet de sécurité : la règle d'escalade « Astreinte » prévient à nouveau si l'OT n'est pas pris en charge à temps.
                </p>
            </div>

            {{-- Équipes --}}
            <div class="grid gap-6 md:grid-cols-2">
                @foreach ([
                    ["Journée ({$dayStart} – {$dayEnd})", 'Managers', $dayTeam],
                    ["Nuit ({$dayEnd} – {$dayStart})", 'Administrateurs maintenance', $nightTeam],
                ] as [$title, $who, $team])
                    <div class="bg-white p-6 shadow-sm rounded-lg">
                        <h3 class="font-semibold text-ink-deep">{{ $title }}</h3>
                        <p class="text-xs text-ink-muted">{{ $who }} qui reçoivent les alertes</p>
                        <ul class="mt-3 space-y-1 text-sm">
                            @forelse ($team as $member)
                                <li class="flex justify-between gap-2">
                                    <a href="{{ route('users.edit', $member) }}" class="text-blue hover:underline">{{ $member->name }}</a>
                                    @if ($member->phone)
                                        <span class="font-mono text-ink-muted">{{ $member->phone }}</span>
                                    @else
                                        <span class="text-red text-xs font-medium">sans téléphone</span>
                                    @endif
                                </li>
                            @empty
                                <li class="text-warn-ink">Personne — les alertes de cette plage iront à l'autre équipe.</li>
                            @endforelse
                        </ul>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-ink-muted">
                Mode actuel des alertes téléphone : <strong>{{ config('services.phone_alerts.driver') === 'log' ? 'démonstration (écrites dans le journal, rien n\'est envoyé)' : config('services.phone_alerts.driver') }}</strong>.
            </p>
        </div>
    </div>
</x-app-layout>
