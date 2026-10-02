<x-app-layout crumb="Signalement rapide" page-title="Signalement envoyé">
    <div class="max-w-xl w-full mx-auto flex flex-col items-center gap-5 text-center py-6">
        <div class="w-32 h-32 rounded-full bg-green text-white flex items-center justify-center text-7xl shadow-lg">✓</div>

        <div class="flex flex-col gap-1.5">
            <h2 class="text-[22px] font-semibold">Merci ! C'est envoyé.</h2>
            <p class="text-[15px] text-[#4A463E]">La maintenance est prévenue.</p>
        </div>

        <div class="w-full bg-white border border-line rounded-xl p-4 flex items-center gap-3 text-left">
            <span class="text-3xl">{{ $workOrder->priority?->code === 'urgente' ? '🚨' : '📋' }}</span>
            <div class="min-w-0">
                <div class="text-[15px] font-semibold truncate">{{ $workOrder->room?->label ?? 'Parties communes' }}</div>
                <div class="text-[12.5px] text-ink-grey">{{ $workOrder->code() }} · {{ $workOrder->priority?->code === 'urgente' ? 'URGENT' : 'reçu' }}</div>
            </div>
        </div>

        <a href="{{ route('quick-reports.create') }}" class="w-full h-16 rounded-xl bg-gold text-navy flex items-center justify-center gap-3 text-[17px] font-bold">
            <span class="text-2xl">🎤</span> Nouveau signalement
        </a>
        <a href="{{ route('work-orders.index') }}" class="w-full h-14 rounded-xl border-2 border-line flex items-center justify-center gap-3 text-[15px] font-semibold text-navy">
            <span class="text-2xl">📋</span> Mes signalements
        </a>
    </div>
</x-app-layout>
