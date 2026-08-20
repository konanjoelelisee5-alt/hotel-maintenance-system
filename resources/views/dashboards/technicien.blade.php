<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-navy-900">Mon espace technicien</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($activeSession)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-emerald-800">Intervention en cours</p>
                        <p class="text-sm text-emerald-700">{{ $activeSession->title }}</p>
                    </div>
                    <a href="{{ route('work-orders.show', $activeSession) }}"
                       class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-md hover:bg-emerald-700">
                        Reprendre
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">OT à traiter</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $myOpenWorkOrders->count() }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Planifiés aujourd'hui</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $todayScheduled->count() }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Terminés cette semaine</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $completedThisWeek }}</p>
                </div>
            </div>

            @if ($todayScheduled->isNotEmpty())
                <div class="bg-white rounded-xl border border-slate-200">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="font-semibold text-navy-900">Planning du jour</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($todayScheduled as $wo)
                            <a href="{{ route('work-orders.show', $wo) }}" class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition">
                                <span class="text-sm font-semibold text-navy-700 w-14">{{ $wo->scheduled_at->format('H:i') }}</span>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-navy-900">{{ $wo->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $wo->room?->number ?? 'Lieu non défini' }}</p>
                                </div>
                                <x-work-order-priority-badge :priority="$wo->priority" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-semibold text-navy-900">Mes ordres de travail ouverts</h3>
                    <a href="{{ route('work-orders.index') }}" class="text-sm text-navy-600 hover:underline">Voir tout</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($myOpenWorkOrders as $wo)
                        <a href="{{ route('work-orders.show', $wo) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition">
                            <div>
                                <p class="text-sm font-medium text-navy-900">{{ $wo->title }}</p>
                                <p class="text-xs text-slate-500">{{ $wo->room?->number ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-work-order-priority-badge :priority="$wo->priority" />
                                <x-work-order-status-badge :status="$wo->status" />
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500 text-center">Aucun OT en cours. Bon travail !</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>