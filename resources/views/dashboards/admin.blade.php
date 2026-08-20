<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-navy-900">Tableau de bord — Administration</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Cartes statistiques -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Total OT</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $totalWorkOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">OT ouverts</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $openWorkOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border {{ $slaBreached > 0 ? 'border-red-200 bg-red-50' : 'border-slate-200' }} p-5">
                    <p class="text-sm {{ $slaBreached > 0 ? 'text-red-600' : 'text-slate-500' }}">SLA dépassés</p>
                    <p class="text-3xl font-semibold {{ $slaBreached > 0 ? 'text-red-700' : 'text-navy-900' }} mt-1">{{ $slaBreached }}</p>
                </div>
                <div class="bg-white rounded-xl border {{ $lowStockParts > 0 ? 'border-gold-200 bg-gold-50' : 'border-slate-200' }} p-5">
                    <p class="text-sm {{ $lowStockParts > 0 ? 'text-gold-700' : 'text-slate-500' }}">Pièces sous seuil</p>
                    <p class="text-3xl font-semibold {{ $lowStockParts > 0 ? 'text-gold-700' : 'text-navy-900' }} mt-1">{{ $lowStockParts }}</p>
                </div>
                <div class="bg-white rounded-xl border {{ $duePreventivePlans > 0 ? 'border-navy-200 bg-navy-50' : 'border-slate-200' }} p-5">
                    <p class="text-sm {{ $duePreventivePlans > 0 ? 'text-navy-700' : 'text-slate-500' }}">Maintenances préventives (7j)</p>
                    <p class="text-3xl font-semibold {{ $duePreventivePlans > 0 ? 'text-navy-800' : 'text-navy-900' }} mt-1">{{ $duePreventivePlans }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- OT récents -->
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
                                    <p class="text-xs text-slate-500">{{ $wo->assignee?->name ?? 'Non assigné' }} — {{ $wo->created_at->diffForHumans() }}</p>
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

                <!-- Accès rapides -->
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <h3 class="font-semibold text-navy-900 mb-4">Accès rapides</h3>
                    <div class="space-y-2">
                        <a href="{{ route('users.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-md hover:bg-slate-50 text-sm text-slate-700">
                            <span>Utilisateurs actifs</span>
                            <span class="font-semibold text-navy-900">{{ $totalUsers }}</span>
                        </a>
                        <a href="{{ route('parts.index', ['low_stock' => 1]) }}" class="flex items-center justify-between px-3 py-2.5 rounded-md hover:bg-slate-50 text-sm text-slate-700">
                            <span>Voir le stock bas</span>
                            <span class="font-semibold text-gold-600">{{ $lowStockParts }}</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-md hover:bg-slate-50 text-sm text-slate-700">
                            <span>Rapports & KPI</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('maintenance-plans.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-md hover:bg-slate-50 text-sm text-slate-700">
                            <span>Maintenance préventive</span>
                            <span class="font-semibold text-navy-900">{{ $duePreventivePlans }}</span>
                        </a>
                        <a href="{{ route('activity-logs.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-md hover:bg-slate-50 text-sm text-slate-700">
                            <span>Journal d'activité</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>