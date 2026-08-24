<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">OT #{{ $workOrder->id }}</p>
                <h2 class="font-semibold text-xl text-navy-900 leading-tight">{{ $workOrder->title }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (in_array(auth()->user()->role, ['admin', 'manager']))
                    <a href="{{ route('work-orders.schedule', $workOrder) }}"
                       class="px-4 py-2 bg-navy-700 text-white text-sm font-medium rounded-md hover:bg-navy-800">
                        Planifier
                    </a>
                @endif
                @can('update', $workOrder)
                    <a href="{{ route('work-orders.edit', $workOrder) }}"
                       class="px-4 py-2 bg-white border border-slate-300 text-slate-700 text-sm font-medium rounded-md hover:bg-slate-50">
                        Modifier
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Bandeau statut / priorité / description -->
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <x-work-order-status-badge :status="$workOrder->status" />
                    <x-work-order-priority-badge :priority="$workOrder->priority" />
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                        {{ $workOrder->type->label }}
                    </span>
                </div>
                <p class="text-slate-700 leading-relaxed">
                    {{ $workOrder->description ?: 'Aucune description fournie.' }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Colonne principale : actions et suivi -->
                <div class="lg:col-span-2 space-y-6">

                    @can('intervene', $workOrder)
                        @include('work-orders.partials.status-form')
                        @include('work-orders.partials.intervention-tracking')
                        @include('work-orders.partials.intervention-report')
                    @endcan

                    @if (in_array(auth()->user()->role, ['admin', 'manager']))
                        @include('work-orders.partials.quality-control')
                    @endif

                    @if ($workOrder->correctionRequests->isNotEmpty())
                        @include('work-orders.partials.correction-requests')
                    @endif

                    @include('work-orders.partials.comments')

                </div>

                <!-- Colonne latérale : informations et ressources -->
                <div class="space-y-6">
                    @include('work-orders.partials.info-card')

                    @if ($workOrder->sla_policy_id)
                        @include('work-orders.partials.sla-card')
                    @endif

                    @include('work-orders.partials.parts-card')
                    @include('work-orders.partials.attachments-card')
                    @include('work-orders.partials.status-history-card')
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
