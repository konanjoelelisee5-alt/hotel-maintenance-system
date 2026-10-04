<x-app-layout crumb="Signalement rapide" page-title="Signalement envoyé">
    @php $urgent = $workOrder->priority?->code === 'urgente'; @endphp
    <div class="max-w-[460px] w-full mx-auto flex flex-col items-center gap-3.5 text-center py-8">
        <span class="w-28 h-28 rounded-full bg-green text-white flex items-center justify-center shadow-[0_0_0_14px_rgba(30,122,85,.12),0_0_0_30px_rgba(30,122,85,.06)]">
            <x-nav-icon name="check" class="!w-14 !h-14" />
        </span>

        <h2 class="mt-7 text-[28px] font-extrabold tracking-[-.025em]">Merci ! C'est envoyé.</h2>
        <p class="text-[15px] leading-relaxed text-[#5C6472]">La maintenance est prévenue. Vous recevrez une notification dès qu'un technicien s'en occupe.</p>

        <div class="w-full mt-2.5 bg-white border border-line rounded-[18px] px-4 py-3.5 flex items-center justify-between gap-3 text-left">
            <span class="flex items-center gap-2 min-w-0 text-[15px] font-bold">
                <x-nav-icon name="pin" class="!w-5 !h-5 flex-shrink-0 text-gold-600" />
                <span class="truncate">{{ $workOrder->room?->label ?? 'Parties communes' }}</span>
                @if ($urgent)
                    <span class="flex-shrink-0 h-5 px-1.5 rounded-[5px] bg-[#FBE7E5] text-red text-[10px] font-bold tracking-[.04em] flex items-center">URGENT</span>
                @endif
            </span>
            <span class="font-mono text-[13px] font-semibold text-[#5C6472] flex-shrink-0">{{ $workOrder->code() }}</span>
        </div>

        <div class="w-full mt-3.5 flex flex-col gap-2.5">
            @can('view', $workOrder)
                <a href="{{ route('work-orders.show', $workOrder) }}" class="h-14 rounded-2xl bg-navy text-white flex items-center justify-center gap-2.5 text-[16px] font-bold hover:bg-navy-light transition">
                    Suivre ce signalement <x-nav-icon name="arrow-right" class="!w-5 !h-5" />
                </a>
            @endcan
            <div class="flex gap-2.5">
                <a href="{{ route('quick-reports.create') }}" class="flex-1 h-[52px] rounded-2xl border-[1.5px] border-navy flex items-center justify-center gap-2 text-[15px] font-bold text-navy">
                    <x-nav-icon name="mic" class="!w-5 !h-5" /> Nouveau
                </a>
                <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="flex-1 h-[52px] rounded-2xl border-[1.5px] border-navy flex items-center justify-center text-[15px] font-bold text-navy">
                    Mes signalements
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
