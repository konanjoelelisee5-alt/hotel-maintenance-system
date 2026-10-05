{{-- Confirmation du signalement : coche verte, lieu et référence de l'OT, puis
     « Suivre ce signalement », « Nouveau signalement » et « Accueil ». --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $category = $hk::category($workOrder);
    $urgent = $workOrder->priority?->code === 'urgente';
@endphp

<x-app-layout crumb="Signalement" page-title="Signalement envoyé">
    <div class="w-full max-w-[560px] mx-auto tab:mt-6">
        <section class="bg-white border border-line rounded-xl overflow-hidden">
            <div class="px-6 pt-8 pb-6 flex flex-col items-center text-center gap-3 border-b border-line-soft">
                <span class="w-16 h-16 rounded-full bg-[#E6F3EC] text-green flex items-center justify-center ring-8 ring-[#E6F3EC]/50">
                    <x-hk.icon name="check" :size="30" />
                </span>
                <h2 class="m-0 mt-2 text-[20px] font-semibold text-navy tracking-tight">Signalement envoyé</h2>
                <p class="m-0 text-[13.5px] text-[#6C6658] max-w-[380px] leading-relaxed">La maintenance est prévenue. Vous recevrez une notification à chaque étape de la réparation.</p>
            </div>

            <dl class="m-0 px-6 py-2 text-[13.5px]">
                <div class="flex items-center justify-between gap-4 py-3 border-b border-line-soft">
                    <dt class="text-ink-grey">Référence</dt>
                    <dd class="m-0 font-mono text-[13px] text-navy font-medium">{{ $workOrder->code() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3 border-b border-line-soft">
                    <dt class="text-ink-grey">Lieu</dt>
                    <dd class="m-0 font-medium text-navy text-right">{{ $workOrder->room?->label ?? 'Parties communes' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3 border-b border-line-soft">
                    <dt class="text-ink-grey">Problème</dt>
                    <dd class="m-0 font-medium text-navy flex items-center gap-1.5">
                        <x-hk.icon :name="$hk::categoryIcon($category)" :size="15" class="text-gold" />
                        {{ $category ? $hk::categoryLabel($category) : $workOrder->title }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3 border-b border-line-soft">
                    <dt class="text-ink-grey">Statut</dt>
                    <dd class="m-0"><x-hk.status :status="$workOrder->status" :urgent="$urgent" /></dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-ink-grey">Envoyé</dt>
                    <dd class="m-0 font-mono text-[13px] text-navy">{{ $workOrder->created_at->format('d/m/Y à H\hi') }}</dd>
                </div>
            </dl>

            <div class="px-6 py-5 bg-paper/60 border-t border-line flex flex-col gap-2.5">
                <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-primary btn-lg w-full"><x-hk.icon name="activity" :size="16" /> Suivre ce signalement</a>
                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('quick-reports.create') }}" class="btn btn-secondary btn-lg"><x-hk.icon name="plus" :size="16" /> Nouveau</a>
                    <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="btn btn-secondary btn-lg"><x-hk.icon name="home" :size="16" /> Accueil</a>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
