<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planifier l\'OT') }} #{{ $workOrder->id }} — {{ $workOrder->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-lg">

                @if (session('warning'))
                    <div class="mb-4 p-4 bg-orange-100 text-orange-800 rounded-md text-sm">
                        {{ session('warning') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('work-orders.schedule.store', $workOrder) }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="assigned_to" value="Technicien" />
                        <select id="assigned_to" name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
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
                        <a href="{{ route('work-orders.show', $workOrder) }}" class="px-4 py-2 text-sm text-gray-600 hover:underline">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Planifier
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>