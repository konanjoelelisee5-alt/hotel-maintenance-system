<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planning général') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <!-- Filtre par technicien -->
            <div class="bg-white p-4 shadow-sm rounded-lg flex flex-wrap gap-4 items-center">
                <label class="text-sm font-medium text-gray-700">Filtrer par technicien :</label>
                <select id="technician-filter" class="border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Tous les techniciens</option>
                    @foreach ($technicians as $technician)
                        <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Calendrier -->
            <div class="bg-white p-4 shadow-sm rounded-lg">
                <div id="calendar"></div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script src="https://unpkg.com/@fullcalendar/core@6.1.11/locales/fr.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('calendar');
                const technicianFilter = document.getElementById('technician-filter');

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    locale: 'fr',
                    initialView: 'timeGridWeek',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                    },
                    height: 'auto',
                    slotMinTime: '00:00:00',
                    slotMaxTime: '24:00:00',
                    events: function (info, successCallback, failureCallback) {
                        let url = new URL("{{ route('planning.events') }}");
                        url.searchParams.set('start', info.startStr);
                        url.searchParams.set('end', info.endStr);
                        if (technicianFilter.value) {
                            url.searchParams.set('technician_id', technicianFilter.value);
                        }

                        fetch(url)
                            .then(response => response.json())
                            .then(data => successCallback(data))
                            .catch(error => failureCallback(error));
                    },
                });

                calendar.render();

                technicianFilter.addEventListener('change', function () {
                    calendar.refetchEvents();
                });
            });
        </script>
    @endpush
</x-app-layout>