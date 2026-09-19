<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Planning de') }} {{ $technician->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (in_array(auth()->user()->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Manager], true))
                <div class="flex gap-3">
                    <a href="{{ route('planning.skills.edit', $technician) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Compétences
                    </a>
                    <a href="{{ route('planning.availabilities.index', $technician) }}" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">
                        Disponibilités
                    </a>
                </div>
            @endif

            <div class="bg-white p-4 shadow-sm rounded-lg">
                <div class="overflow-x-auto">
                    <div id="calendar" class="min-w-[640px]"></div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script src="https://unpkg.com/@fullcalendar/core@6.1.11/locales/fr.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const calendarEl = document.getElementById('calendar');

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
                        url.searchParams.set('technician_id', '{{ $technician->id }}');

                        fetch(url)
                            .then(response => response.json())
                            .then(data => successCallback(data))
                            .catch(error => failureCallback(error));
                    },
                });

                calendar.render();
            });
        </script>
    @endpush
</x-app-layout>