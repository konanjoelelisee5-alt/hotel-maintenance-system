<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-navy-900">Tableau de bord — Manager</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">OT ouverts</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $openWorkOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border {{ $urgentOpen > 0 ? 'border-red-200 bg-red-50' : 'border-slate-200' }} p-5">
                    <p class="text-sm {{ $urgentOpen > 0 ? 'text-red-600' : 'text-slate-500' }}">Urgents ouverts</p>
                    <p class="text-3xl font-semibold {{ $urgentOpen > 0 ? 'text-red-700' : 'text-navy-900' }} mt-1">{{ $urgentOpen }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">En attente qualité</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $awaitingQualityControl }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Achats en cours</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $pendingPurchaseOrders }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200">
                    <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-semibold text-navy-900">Ordres de travail récents</h3>
                        <a href="{{ route('work-orders.index') }}" class="text-sm text-navy-600 hover:underline">Voir tout</a>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($recentWorkOrders as $wo)
                            <a href="{{ route('work-orders.show', $wo) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition">
                                <div>
                                    <p class="text-sm font-medium text-navy-900">{{ $wo->title }}</p>
                                    <p class="text-xs text-slate-500">Signalé par {{ $wo->reporter?->name }} — {{ $wo->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-work-order-priority-badge :priority="$wo->priority" />
                                    <x-work-order-status-badge :status="$wo->status" />
                                </div>
                            </a>
                        @empty
                            <p class="px-5 py-6 text-sm text-slate-500 text-center">Aucun ordre de travail.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3 class="font-semibold text-navy-900 mb-4">Charge des techniciens</h3>
                    <div class="space-y-3">
                        @forelse ($technicianWorkload as $tech)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-700">{{ $tech->name }}</span>
                                <span class="inline-flex items-center justify-center h-6 min-w-6 px-1.5 rounded-full text-xs font-semibold
                                    {{ $tech->open_count > 3 ? 'bg-red-100 text-red-700' : 'bg-navy-100 text-navy-700' }}">
                                    {{ $tech->open_count }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Aucun technicien actif.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200">
                <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-semibold text-navy-900">Maintenance préventive — échéances à venir (14 jours)</h3>
                    <a href="{{ route('maintenance-plans.index') }}" class="text-sm text-navy-600 hover:underline">Voir tout</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($upcomingMaintenancePlans as $plan)
                        <a href="{{ route('maintenance-plans.show', $plan) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition">
                            <div>
                                <p class="text-sm font-medium text-navy-900">{{ $plan->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $plan->equipment?->name ?? ($plan->room ? 'Chambre ' . $plan->room->number : '—') }}
                                    — {{ $plan->frequency_label }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold text-navy-900">{{ $plan->next_due_at?->format('d/m/Y') }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500 text-center">Aucune échéance de maintenance préventive dans les 14 prochains jours.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>