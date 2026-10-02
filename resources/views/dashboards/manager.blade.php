<x-app-layout crumb="Exploitation" page-title="Tableau de bord">
    @php
        $here = fn (array $params) => route('manager.dashboard', array_merge(['filter' => $filter], $params));
    @endphp

    <x-slot:primaryAction>
        <x-dashboard-actions />
    </x-slot:primaryAction>

    @include('dashboards.partials._kpi-band')

    {{-- Même structure que le tableau de bord admin ; la colonne latérale garde
         les panneaux propres au manager (charge des techniciens, achats et stock). --}}
    <div class="grid gap-5 items-start min-[1100px]:grid-cols-[minmax(0,1fr)_320px] min-[1500px]:grid-cols-[minmax(0,1fr)_380px]">
        @include('dashboards.partials._queue-table')

        <div class="flex flex-col gap-5 min-w-0">
            @include('dashboards.partials._bars-card', ['panel' => $sideA])
            @include('dashboards.partials._list-card', ['panel' => $sideB, 'link' => ['Achats →', route('purchase-orders.index', ['tab' => 'open'])]])
        </div>
    </div>

    @include('dashboards.partials._timeline')
</x-app-layout>
