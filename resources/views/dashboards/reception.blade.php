<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-navy-900">Mon espace Réception</h2>
            <a href="{{ route('work-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-navy-800 text-white text-sm font-medium rounded-md hover:bg-navy-700">
                + Signaler un problème
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Signalements ouverts</p>
                    <p class="text-3xl font-semibold text-navy-900 mt-1">{{ $myOpenCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">Signalements résolus</p>
                    <p class="text-3xl font-semibold text-emerald-700 mt-1">{{ $myResolvedCount }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-navy-900">Mes signalements</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($myReports as $wo)
                        <a href="{{ route('work-orders.show', $wo) }}" class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition">
                            <div>
                                <p class="text-sm font-medium text-navy-900">{{ $wo->title }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $wo->room?->number ?? '—' }} ·
                                    {{ $wo->assignee ? 'Assigné à ' . $wo->assignee->name : 'Non assigné' }}
                                </p>
                            </div>
                            <x-work-order-status-badge :status="$wo->status" />
                        </a>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-500 text-center">Aucun signalement pour le moment.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>