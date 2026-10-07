<x-app-layout :crumb="'Ordres de travail / '.$workOrder->code()" :page-title="'Planifier l\'intervention'" :back-route="route('work-orders.show', $workOrder)">
    <div>
        <div class="w-full max-w-2xl">
            <div class="bg-white p-6 shadow-sm rounded-lg">


                <form method="POST" action="{{ route('work-orders.schedule.store', $workOrder) }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="assigned_to" value="Technicien" />
                        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-line rounded-md shadow-sm" required>
                            <option value="">-- Sélectionner --</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected(old('assigned_to', $workOrder->assigned_to) == $technician->id)>
                                    {{ $technician->name }}
                                    @if ($technician->skills->isNotEmpty())
                                        ({{ $technician->skills->pluck('name')->join(', ') }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="scheduled_at" value="Date et heure planifiées" />
                        <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full"
                            :value="old('scheduled_at', $workOrder->scheduled_at?->format('Y-m-d\TH:i'))" required />
                        <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="estimated_duration_minutes" value="Durée estimée (minutes)" />
                        <x-text-input id="estimated_duration_minutes" name="estimated_duration_minutes" type="number" min="15" max="480" step="15"
                            class="mt-1 block w-full" :value="old('estimated_duration_minutes', $workOrder->estimated_duration_minutes ?? 60)" required />
                        <x-input-error :messages="$errors->get('estimated_duration_minutes')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-ghost">
                            Annuler
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Planifier
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>