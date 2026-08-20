<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                OT #{{ $workOrder->id }} — {{ $workOrder->title }}
            </h2>
            <div class="flex gap-2">
                @if (in_array(auth()->user()->role, ['admin', 'manager']))
                    <a href="{{ route('work-orders.schedule', $workOrder) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        Planifier
                    </a>
                @endif
                @can('update', $workOrder)
                    <a href="{{ route('work-orders.edit', $workOrder) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Modifier
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Informations générales -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <div class="flex flex-wrap gap-3 mb-4">
                    <x-work-order-status-badge :status="$workOrder->status" />
                    <x-work-order-priority-badge :priority="$workOrder->priority" />
                </div>

                <p class="text-gray-700 mb-4">{{ $workOrder->description ?: 'Aucune description fournie.' }}</p>

                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div><span class="font-medium">Lieu :</span> {{ $workOrder->room?->number ?? '—' }}</div>
                    <div><span class="font-medium">Équipement :</span> {{ $workOrder->equipment?->name ?? '—' }}</div>
                    <div><span class="font-medium">Assigné à :</span> {{ $workOrder->assignee?->name ?? 'Non assigné' }}</div>
                    <div><span class="font-medium">Signalé par :</span> {{ $workOrder->reporter?->name }}</div>
                    <div><span class="font-medium">Créé le :</span> {{ $workOrder->created_at->format('d/m/Y H:i') }}</div>
                    <div><span class="font-medium">Échéance :</span> {{ $workOrder->due_date?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
            </div>

            <!-- Changement de statut -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Changer le statut</h3>
                <form method="POST" action="{{ route('work-orders.status.update', $workOrder) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="flex gap-4">
                        <select name="status" class="block w-1/3 border-gray-300 rounded-md shadow-sm" required>
                            <option value="ouvert" @selected($workOrder->status === 'ouvert')>Ouvert</option>
                            <option value="en_cours" @selected($workOrder->status === 'en_cours')>En cours</option>
                            <option value="en_attente" @selected($workOrder->status === 'en_attente')>En attente</option>
                            <option value="resolu" @selected($workOrder->status === 'resolu')>Résolu</option>
                            <option value="ferme" @selected($workOrder->status === 'ferme')>Fermé</option>
                        </select>

                        <input type="text" name="note" placeholder="Note (optionnel)"
                               class="block flex-1 border-gray-300 rounded-md shadow-sm">

                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Mettre à jour
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </form>
            </div>

            <!-- Suivi de l'intervention -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Suivi de l'intervention</h3>

                @php
                    $activeSession = $workOrder->activeSession();
                @endphp

                <div class="flex items-center justify-between mb-4">
                    <div>
                        @if ($activeSession)
                            <p class="text-sm text-gray-600">Intervention en cours depuis :</p>
                            <p class="text-2xl font-mono font-bold text-green-600" id="timer" data-started-at="{{ $activeSession->started_at->toIso8601String() }}">
                                00:00:00
                            </p>
                        @else
                            <p class="text-sm text-gray-600">Aucune intervention en cours.</p>
                        @endif
                    </div>

                    <div>
                        @if ($activeSession)
                            <form method="POST" action="{{ route('work-orders.sessions.stop', $workOrder) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                                    ⏸ Arrêter l'intervention
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('work-orders.sessions.start', $workOrder) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                    ▶ Démarrer l'intervention
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Pièces réservées -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Pièces pour cet OT</h3>

                <form method="POST" action="{{ route('work-orders.reservations.store', $workOrder) }}" class="flex gap-3 items-end mb-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Pièce</label>
                        <select name="part_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                            @foreach (\App\Models\Part::where('is_active', true)->orderBy('name')->get() as $part)
                                <option value="{{ $part->id }}">{{ $part->name }} (disponible : {{ $part->quantity_available }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Quantité</label>
                        <input type="number" name="quantity" min="1" value="1" class="w-24 border-gray-300 rounded-md shadow-sm text-sm" required>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Réserver
                    </button>
                </form>
                <x-input-error :messages="$errors->get('part_id')" class="mt-2" />

                @forelse ($workOrder->partReservations as $reservation)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <div>
                            {{ $reservation->part->name }} — {{ $reservation->quantity }} {{ $reservation->part->unit }}
                            <span class="px-2 py-0.5 text-xs rounded-full ml-2
                                {{ $reservation->status === 'reservee' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $reservation->status === 'sortie' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $reservation->status === 'annulee' ? 'bg-gray-100 text-gray-500' : '' }}">
                                {{ $reservation->status_label }}
                            </span>
                        </div>

                        @if ($reservation->status === 'reservee')
                            <div class="flex gap-3">
                                <form method="POST" action="{{ route('work-orders.reservations.withdraw', [$workOrder, $reservation]) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-600 hover:underline">Confirmer sortie</button>
                                </form>
                                <form method="POST" action="{{ route('work-orders.reservations.cancel', [$workOrder, $reservation]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Annuler</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucune pièce réservée pour cet OT.</p>
                @endforelse
            </div>

                <!-- Temps total travaillé -->
                <p class="text-sm text-gray-500 mb-3">
                    Temps total enregistré : <strong>{{ $workOrder->total_worked_minutes }} minutes</strong>
                </p>

                <!-- Historique des sessions -->
                @if ($workOrder->interventionSessions->isNotEmpty())
                    <div class="border-t pt-3 space-y-2">
                        @foreach ($workOrder->interventionSessions as $session)
                            <div class="text-xs text-gray-500 flex justify-between">
                                <span>
                                    {{ $session->technician->name }} —
                                    {{ $session->started_at->format('d/m/Y H:i') }}
                                    @if ($session->ended_at)
                                        → {{ $session->ended_at->format('H:i') }}
                                    @else
                                        <span class="text-green-600 font-medium">(en cours)</span>
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

            <!-- Pièces jointes -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Photos / Documents</h3>

                <form method="POST" action="{{ route('work-orders.attachments.store', $workOrder) }}"
                      enctype="multipart/form-data" class="mb-4 flex gap-3 items-center">
                    @csrf
                    <input type="file" name="files[]" multiple class="text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Envoyer
                    </button>
                </form>
                <x-input-error :messages="$errors->get('files')" class="mt-2" />

                @if ($workOrder->attachments->isEmpty())
                    <p class="text-sm text-gray-500">Aucune pièce jointe.</p>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach ($workOrder->attachments as $attachment)
                            <div class="border rounded-md p-2 text-center">
                                @if (str_starts_with($attachment->mime_type, 'image/'))
                                    <img src="{{ $attachment->url }}" class="h-24 w-full object-cover rounded mb-2">
                                @else
                                    <div class="h-24 flex items-center justify-center bg-gray-100 rounded mb-2 text-xs text-gray-500">
                                        📄 Document
                                    </div>
                                @endif
                                <p class="text-xs text-gray-600 truncate">{{ $attachment->original_name }}</p>

                                <form method="POST" action="{{ route('work-orders.attachments.destroy', [$workOrder, $attachment]) }}"
                                      onsubmit="return confirm('Supprimer ce fichier ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline mt-1">Supprimer</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Commentaires -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Commentaires</h3>

                <form method="POST" action="{{ route('work-orders.comments.store', $workOrder) }}" class="mb-4 space-y-2">
                    @csrf
                    <textarea name="content" rows="3" placeholder="Ajouter un commentaire..."
                        class="block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                        Publier
                    </button>
                </form>

                <div class="space-y-3">
                    @forelse ($workOrder->comments as $comment)
                        <div class="border-t pt-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span class="font-medium text-gray-700">{{ $comment->user->name }}</span>
                                <span>{{ $comment->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-700">{{ $comment->content }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucun commentaire pour le moment.</p>
                    @endforelse
                </div>
            </div>

            <!-- Historique des statuts -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Historique des statuts</h3>

                <ol class="space-y-3">
                    @foreach ($workOrder->statusHistories as $history)
                        <li class="text-sm border-l-2 border-gray-200 pl-4">
                            <span class="text-gray-500">{{ $history->created_at->format('d/m/Y H:i') }}</span> —
                            <span class="font-medium">{{ $history->changedBy->name }}</span>
                            a changé le statut de
                            <em>{{ $history->old_status ?? 'création' }}</em> à
                            <strong>{{ $history->new_status }}</strong>
                            @if ($history->note)
                                <br><span class="text-gray-600 italic">"{{ $history->note }}"</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>


            <div class="bg-white p-6 shadow-sm rounded-lg">
                <h3 class="font-medium text-gray-800 mb-4">Rapport d'intervention</h3>

                @if ($workOrder->interventionReport?->is_signed)
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-800">
                       <!-- Contrôle qualité -->
            <div class="bg-white p-6 shadow-sm rounded-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-medium text-gray-800">Contrôle qualité</h3>
                    @if ($workOrder->status === 'resolu')
                        <a href="{{ route('quality-controls.create', $workOrder) }}"
                           class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                            Démarrer un contrôle qualité
                        </a>
                    @endif
                </div>

                @forelse ($workOrder->qualityControls as $qc)
                    <div class="flex justify-between items-center border-t py-3 text-sm">
                        <span>{{ $qc->created_at->format('d/m/Y H:i') }} — {{ $qc->reviewer->name }}</span>
                        <div class="flex items-center gap-3">
                            <x-quality-control-status-badge :status="$qc->status" />
                            <a href="{{ route('quality-controls.show', $qc) }}" class="text-indigo-600 hover:underline">Voir</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Aucun contrôle qualité pour cet OT.</p>
                @endforelse
            </div>

            <!-- Demandes de correction -->
            @if ($workOrder->correctionRequests->isNotEmpty())
                <div class="bg-white p-6 shadow-sm rounded-lg">
                    <h3 class="font-medium text-gray-800 mb-4">Demandes de correction</h3>

                    @foreach ($workOrder->correctionRequests as $correction)
                        <div class="border-t py-3 text-sm">
                            <div class="flex justify-between items-start">
                                <p class="text-gray-700">{{ $correction->description }}</p>
                                @if ($correction->status === 'ouverte')
                                    <form method="POST" action="{{ route('correction-requests.resolve', $correction) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs text-indigo-600 hover:underline whitespace-nowrap ml-2">
                                            Marquer traitée
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-green-600 whitespace-nowrap ml-2">✓ Traitée</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $correction->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
            </div>
            @endif ✓ Rapport signé par {{ $workOrder->interventionReport->signed_by_name }}
                        le {{ $workOrder->interventionReport->signed_at->format('d/m/Y à H:i') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('work-orders.report.store', $workOrder) }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="work_performed" value="Travail effectué" />
                        <textarea id="work_performed" name="work_performed" rows="4" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('work_performed', $workOrder->interventionReport?->work_performed) }}</textarea>
                        <x-input-error :messages="$errors->get('work_performed')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="parts_used" value="Pièces / matériel utilisés" />
                        <textarea id="parts_used" name="parts_used" rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('parts_used', $workOrder->interventionReport?->parts_used) }}</textarea>
                        <x-input-error :messages="$errors->get('parts_used')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="recommendations" value="Recommandations" />
                        <textarea id="recommendations" name="recommendations" rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('recommendations', $workOrder->interventionReport?->recommendations) }}</textarea>
                        <x-input-error :messages="$errors->get('recommendations')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="signed_by_name" value="Nom du signataire (client / responsable)" />
                        <x-text-input id="signed_by_name" name="signed_by_name" type="text" class="mt-1 block w-full"
                            :value="old('signed_by_name', $workOrder->interventionReport?->signed_by_name)" />
                        <x-input-error :messages="$errors->get('signed_by_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Signature électronique" />
                        <div class="mt-1 border border-gray-300 rounded-md bg-gray-50">
                            <canvas id="signature-pad" class="w-full touch-none" height="150"></canvas>
                        </div>
                        <button type="button" id="clear-signature" class="mt-2 text-xs text-gray-500 hover:underline">
                            Effacer la signature
                        </button>
                        <input type="hidden" name="signature" id="signature-input">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Enregistrer le rapport
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const timerEl = document.getElementById('timer');

                if (timerEl) {
                    const startedAt = new Date(timerEl.dataset.startedAt);

                    function updateTimer() {
                        const now = new Date();
                        const diffSeconds = Math.floor((now - startedAt) / 1000);

                        const hours = String(Math.floor(diffSeconds / 3600)).padStart(2, '0');
                        const minutes = String(Math.floor((diffSeconds % 3600) / 60)).padStart(2, '0');
                        const seconds = String(diffSeconds % 60).padStart(2, '0');

                        timerEl.textContent = `${hours}:${minutes}:${seconds}`;
                    }

                    updateTimer();
                    setInterval(updateTimer, 1000);
                }

                // ===== Signature électronique =====
                const canvas = document.getElementById('signature-pad');

                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    canvas.width = canvas.offsetWidth;

                    let drawing = false;

                    function getPosition(event) {
                        const rect = canvas.getBoundingClientRect();
                        const clientX = event.touches ? event.touches[0].clientX : event.clientX;
                        const clientY = event.touches ? event.touches[0].clientY : event.clientY;
                        return {
                            x: clientX - rect.left,
                            y: clientY - rect.top,
                        };
                    }

                    function startDrawing(event) {
                        drawing = true;
                        const pos = getPosition(event);
                        ctx.beginPath();
                        ctx.moveTo(pos.x, pos.y);
                        event.preventDefault();
                    }

                    function draw(event) {
                        if (!drawing) return;
                        const pos = getPosition(event);
                        ctx.lineTo(pos.x, pos.y);
                        ctx.stroke();
                        event.preventDefault();
                    }

                    function stopDrawing() {
                        drawing = false;
                    }

                    // Souris
                    canvas.addEventListener('mousedown', startDrawing);
                    canvas.addEventListener('mousemove', draw);
                    canvas.addEventListener('mouseup', stopDrawing);
                    canvas.addEventListener('mouseleave', stopDrawing);

                    // Tactile (tablette/mobile)
                    canvas.addEventListener('touchstart', startDrawing);
                    canvas.addEventListener('touchmove', draw);
                    canvas.addEventListener('touchend', stopDrawing);

                    // Bouton "Effacer"
                    document.getElementById('clear-signature').addEventListener('click', function () {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    });

                    // Juste avant l'envoi du formulaire, on convertit le dessin en image base64
                    canvas.closest('form').addEventListener('submit', function () {
                        document.getElementById('signature-input').value = canvas.toDataURL('image/png');
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>