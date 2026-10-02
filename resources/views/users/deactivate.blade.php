<x-app-layout :crumb="'Paramètres / Utilisateurs'" :page-title="'Désactiver '.$user->name" :back-route="route('users.index')">
    @php
        // « Paul — 3 OT en cours · Plomberie, Électricité » : charge et compétences
        // aident à choisir le bon remplaçant sans ouvrir d'autre écran.
        $techLabel = fn ($t) => $t->name.' — '.$t->open_count.' OT en cours'
            .($t->skills->isNotEmpty() ? ' · '.$t->skills->pluck('name')->implode(', ') : '');
    @endphp

    <div>
        <div class="w-full max-w-4xl">
            <form method="POST" action="{{ route('users.deactivate.store', $user) }}" class="space-y-6"
                  data-confirm="Le compte ne pourra plus se connecter ; ses OT ouverts seront réaffectés comme indiqué." data-confirm-title="Désactiver ce compte ?" data-confirm-label="Désactiver le compte" data-confirm-tone="danger">
                @csrf

                @if ($workOrders->isEmpty())
                    <div class="bg-white p-6 shadow-sm rounded-lg text-sm text-gray-700">
                        {{ $user->name }} ({{ $user->role_label }}) n'a aucun ordre de travail en cours : rien à réaffecter.
                        @if ($planCount > 0)
                            <p class="mt-2">{{ $planCount }} plan(s) de maintenance préventive lui sont attribués : ils passeront au remplaçant choisi ci-dessous, ou à l'affectation automatique par compétence.</p>
                        @endif
                    </div>
                @else
                    <div class="p-4 bg-orange-50 border border-orange-200 text-orange-900 rounded-md text-sm">
                        ⚠️ <strong>{{ $user->name }}</strong> a encore <strong>{{ $workOrders->count() }} ordre(s) de travail</strong> en cours.
                        Choisissez qui les reprend avant de désactiver son compte.
                    </div>
                @endif

                @if ($workOrders->isNotEmpty() || $planCount > 0)
                    <div class="bg-white p-6 shadow-sm rounded-lg">
                        <x-input-label for="default_replacement" value="Remplaçant pour tout" />
                        <select id="default_replacement" name="default_replacement" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">— Personne : remettre en attente d'affectation —</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected(old('default_replacement') == $technician->id)>{{ $techLabel($technician) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('default_replacement')" class="mt-2" />
                        @if ($planCount > 0)
                            <p class="text-xs text-gray-500 mt-2">Il reprendra aussi {{ $planCount }} plan(s) de maintenance préventive.</p>
                        @endif
                    </div>
                @endif

                @if ($workOrders->isNotEmpty())
                    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                        <div class="px-6 pt-5 pb-3">
                            <h3 class="font-semibold text-gray-800">Ordre par ordre</h3>
                            <p class="text-sm text-gray-500">Laissez « Remplaçant pour tout », ou choisissez quelqu'un d'autre pour un OT précis (ex. la plomberie au plombier).</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordre de travail</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priorité</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Donner à</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($workOrders as $w)
                                        <tr>
                                            <td class="px-6 py-3 text-sm">
                                                <a href="{{ route('work-orders.show', $w) }}" class="text-gray-900 font-medium hover:underline" target="_blank">{{ $w->title }}</a>
                                                <span class="block text-xs text-gray-500">{{ $w->code() }} · {{ $w->room?->label ?? $w->equipment?->name ?? 'Lieu non précisé' }} · {{ $w->type?->label }}</span>
                                            </td>
                                            <td class="px-6 py-3 text-sm text-gray-600">{{ $w->priority?->label }}</td>
                                            <td class="px-6 py-3 text-sm">
                                                <select name="assignments[{{ $w->id }}]" class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                    <option value="">Remplaçant pour tout</option>
                                                    @foreach ($technicians as $technician)
                                                        <option value="{{ $technician->id }}" @selected(old("assignments.{$w->id}") == $technician->id)>{{ $techLabel($technician) }}</option>
                                                    @endforeach
                                                </select>
                                                <x-input-error :messages="$errors->get('assignments.'.$w->id)" class="mt-1" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">Annuler</a>
                    <button type="submit" class="px-4 py-2 bg-red-700 text-white text-sm font-medium rounded-md hover:bg-red-800">
                        {{ $workOrders->isEmpty() ? 'Désactiver '.$user->name : 'Désactiver '.$user->name.' et donner les OT' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
