{{-- « Signalements » du Housekeeping (WorkOrderController::housekeepingScreen) :
     « En cours » (filtres de statut) ou « Historique » (terminés, regroupés par jour
     comme l'historique de Chrome) ; un OT ouvert s'affiche en fiche lecture seule.
     < 1000 px : la liste OU la fiche ; ≥ 1000 px : les deux côte à côte. L'OT ouvert
     est dans l'adresse (/work-orders/{id}) : l'état survit au redimensionnement. --}}
@php
    $hk = \App\Support\Housekeeping::class;
    $isHead = auth()->user()->isDepartmentHead();
    $pageTitle = $selected ? ($selected->room?->label ?? 'Parties communes').' · '.$selected->code() : $listTitle;
    $keep = fn (array $over) => route('work-orders.index', array_filter(array_merge([
        'filter' => $filter === 'mine' ? 'mine' : null, 'vue' => $vue === 'historique' ? 'historique' : null, 'q' => $q, 'etat' => $etat,
    ], $over)));
    // Historique : un groupe par jour de fin (réparation ou annulation).
    $groups = $vue === 'historique'
        ? $workOrders->getCollection()->groupBy(fn ($w) => ($w->completed_at ?? $w->updated_at)->toDateString())
        : collect(['' => $workOrders->getCollection()])->filter(fn ($items) => $items->isNotEmpty());
@endphp

<x-app-layout :crumb="$selected ? $listTitle : 'Suivi des pannes'" :page-title="$pageTitle"
              :back-route="$selected ? route('work-orders.index', $listQuery) : null">
    <div class="grid gap-5 split:grid-cols-[minmax(320px,400px)_minmax(0,1fr)] items-start">

        {{-- Liste --}}
        <section class="{{ $selected ? 'hidden split:flex' : 'flex' }} flex-col bg-white border border-line rounded-xl overflow-hidden min-w-0 split:sticky split:top-[88px] split:max-h-[calc(100vh-112px)]">
            <div class="flex flex-col gap-3 px-4 pt-4 pb-3 border-b border-line">
                {{-- En cours / Historique --}}
                <div class="grid grid-cols-2 gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line" role="tablist" aria-label="Vue">
                    @foreach (['encours' => 'En cours', 'historique' => 'Historique'] as $key => $label)
                        <a href="{{ $keep(['vue' => $key === 'historique' ? 'historique' : null, 'etat' => null]) }}" role="tab" aria-selected="{{ $vue === $key ? 'true' : 'false' }}"
                           class="h-9 rounded-[7px] flex items-center justify-center gap-1.5 text-[13px] {{ $vue === $key ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey font-medium' }}">
                            <x-hk.icon :name="$key === 'historique' ? 'clock' : 'activity'" :size="14" />
                            {{ $label }} <span class="font-mono text-[11.5px] text-ink-grey">{{ $vueCounts[$key] }}</span>
                        </a>
                    @endforeach
                </div>

                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('work-orders.index') }}" role="search" class="relative flex-1 min-w-0">
                        @if ($filter === 'mine')<input type="hidden" name="filter" value="mine">@endif
                        @if ($vue === 'historique')<input type="hidden" name="vue" value="historique">@endif
                        @if ($etat)<input type="hidden" name="etat" value="{{ $etat }}">@endif
                        <x-hk.icon name="search" :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-grey" />
                        <input type="search" name="q" value="{{ $q }}" placeholder="{{ $vue === 'historique' ? 'Rechercher dans l\'historique' : 'Chambre, problème, référence…' }}" aria-label="Rechercher un signalement"
                               class="w-full h-10 pl-10 pr-3 rounded-[10px] border-line bg-white text-[13.5px] focus:border-navy focus:ring-navy/20">
                    </form>
                    @if ($isHead)
                        {{-- Gouvernante : ses signalements ou ceux de toute l'équipe. --}}
                        <div class="flex-shrink-0 flex items-center gap-0.5 p-[3px] rounded-[10px] bg-line-soft border border-line" role="group" aria-label="Signalements">
                            @foreach (['mine' => 'Les miens', 'all' => 'Équipe'] as $key => $label)
                                <a href="{{ $keep(['filter' => $key === 'mine' ? 'mine' : null]) }}" @if ($filter === $key) aria-current="true" @endif
                                   class="px-2.5 h-[32px] inline-flex items-center rounded-[7px] text-[12.5px] whitespace-nowrap {{ $filter === $key ? 'bg-white text-navy font-semibold shadow-sm' : 'text-ink-grey font-medium' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <x-tabs variant="underline" label="Filtrer par statut" class="-mb-3 -mx-1"
                        :active="$etat" :items="collect($etatLabels)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $counts[$key] ?? 0, 'href' => $keep(['etat' => $key])])->values()->all()" />
            </div>

            <div class="flex flex-col bg-paper/40 split:overflow-y-auto">
                @foreach ($groups as $day => $items)
                    @if ($vue === 'historique')
                        <h3 class="sticky top-0 z-10 m-0 px-4 py-2 bg-paper border-b border-line-soft text-[12px] font-semibold text-[#4A4639]">
                            {{ $hk::dayLabel(\Illuminate\Support\Carbon::parse($day)) }}
                            <span class="font-mono font-normal text-ink-grey">· {{ $items->count() }}</span>
                        </h3>
                    @endif
                    <div class="flex flex-col gap-2.5 p-3">
                        @foreach ($items as $w)
                            <x-hk.ot-card :w="$w" :href="route('work-orders.show', [$w] + $listQuery)" :reporter="$isHead" :active="$selected?->is($w)" />
                        @endforeach
                    </div>
                @endforeach
                @if ($workOrders->isEmpty())
                    <div class="px-5 py-10 flex flex-col items-center gap-2 text-center">
                        <span class="w-[42px] h-[42px] rounded-full bg-line-soft text-ink-grey flex items-center justify-center"><x-hk.icon :name="$vue === 'historique' ? 'clock' : 'inbox'" :size="18" /></span>
                        <span class="text-[14px] font-semibold">{{ $vue === 'historique' ? 'Historique vide' : 'Aucun signalement en cours' }}</span>
                        <span class="text-[12.5px] text-ink-grey">
                            {{ $q !== '' || $etat !== '' ? 'Rien ne correspond à cette recherche.' : ($vue === 'historique' ? 'Les signalements réparés ou annulés apparaîtront ici.' : 'Tout est réglé. Une panne ? Utilisez « Signaler ».') }}
                        </span>
                    </div>
                @endif
                @if ($workOrders->hasPages())
                    <div class="px-3 pb-3">{{ $workOrders->links() }}</div>
                @endif
            </div>
        </section>

        {{-- Fiche --}}
        @if ($selected)
            <div class="min-w-0">
                @include('housekeeping.partials.work-order-detail', ['workOrder' => $selected])
            </div>
        @else
            <div class="hidden split:flex min-h-[420px] rounded-xl border border-dashed border-[#D6D0C4] bg-white/40 flex-col items-center justify-center gap-2 text-center p-8">
                <span class="w-[42px] h-[42px] rounded-full bg-white border border-line text-ink-grey flex items-center justify-center"><x-hk.icon name="panel" :size="18" /></span>
                <span class="text-[14px] font-semibold text-navy">Sélectionnez un signalement</span>
                <span class="text-[12.5px] text-ink-grey max-w-[300px]">Sa fiche s'affiche ici : statut, message vocal, photo et suivi de la réparation.</span>
            </div>
        @endif
    </div>
</x-app-layout>
