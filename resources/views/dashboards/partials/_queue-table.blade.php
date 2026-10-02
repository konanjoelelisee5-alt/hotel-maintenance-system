{{-- File des ordres (admin, manager) : onglets soulignés avec compteur,
     tableau défilant horizontalement (min 760 px), liseré rouge si SLA dépassé. --}}
<section class="bg-white border border-line rounded-xl overflow-hidden min-w-0">
    <div class="flex items-end gap-4 flex-wrap px-6 pt-5 border-b border-line">
        <div class="flex flex-col gap-0.5 pb-3.5">
            <h2 class="text-[17px] font-semibold text-navy">
                {{ match($filter) { 'urgent' => 'Ordres urgents', 'unassigned' => 'Ordres non affectés', 'late' => 'Ordres en retard SLA', 'to_review' => 'Ordres à contrôler', 'waiting' => 'Ordres en attente', default => 'Tous les ordres' } }}
            </h2>
            <div class="text-[12.5px] text-ink-grey">{{ $queue->count() }} ordre(s) affiché(s) · triés par urgence SLA</div>
        </div>

        <x-tabs :items="collect($filters)->map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'], 'count' => $filterCounts[$f['key']] ?? 0, 'href' => $here(['filter' => $f['key']])])->all()"
                :active="$filter" label="Filtres" class="sm:ml-auto max-w-full" />
    </div>

    @if ($queue->isEmpty())
        <div class="px-5 py-11 flex flex-col items-center gap-2 text-center">
            <div class="w-[42px] h-[42px] rounded-full bg-[#E6F3EC] flex items-center justify-center text-green text-[17px]">✓</div>
            <div class="text-[14px] font-semibold">Aucun ordre dans ce filtre</div>
            <div class="text-[12.5px] text-ink-grey max-w-[340px] leading-relaxed">Rien à traiter dans cette vue. Changez d'onglet ci-dessus pour voir le reste des ordres.</div>
        </div>
    @else
        {{-- Téléphone / tablette : cartes d'OT, comme la liste des ordres. --}}
        <div class="lg:hidden flex flex-col gap-3 p-3 sm:p-4">
            @foreach ($queue as $w)
                @include('work-orders.partials._ot-card', ['w' => $w])
            @endforeach
        </div>

        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[760px] text-left border-collapse">
                <thead>
                    <tr class="bg-paper border-b border-line text-[11.5px] font-semibold uppercase tracking-wide text-ink-grey">
                        <th class="pl-6 pr-3 py-3 w-[108px]">Réf.</th>
                        <th class="px-3 py-3">Intervention</th>
                        <th class="px-3 py-3 w-[112px]">Statut</th>
                        <th class="px-3 py-3 w-[170px]">Technicien</th>
                        <th class="pl-3 pr-6 py-3 w-[180px]">SLA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($queue as $w)
                        @php
                            // Règle commune (WorkOrder::slaSummary) : compte à rebours, ou
                            // « Terminé dans le délai / hors délai » pour un OT terminé.
                            $sla = $w->slaSummary();
                            $late = $sla['late'];
                            $slaColor = $sla['color'];
                        @endphp
                        <tr class="border-b border-line-soft last:border-b-0 hover:bg-paper/60 {{ $late ? 'shadow-[inset_3px_0_0_theme(colors.red)]' : '' }}">
                            <td class="pl-6 pr-3 py-4 align-middle font-mono text-[12.5px] text-[#4A4639] whitespace-nowrap">
                                <a href="{{ route('work-orders.show', $w) }}" class="hover:text-navy">{{ $w->code() }}</a>
                            </td>
                            <td class="px-3 py-4 align-middle">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('work-orders.show', $w) }}" class="text-[14.5px] font-semibold text-navy hover:underline">{{ $w->title }}</a>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11.5px] font-semibold whitespace-nowrap" style="background-color: {{ $w->priority->color }}18; color: {{ $w->priority->color }};">{{ $w->priority->label }}</span>
                                </div>
                                <div class="text-[13px] text-[#6C6658] mt-0.5">{{ $w->room?->label ?? '—' }} · {{ $w->equipment?->name ?? $w->type->label }}</div>
                            </td>
                            <td class="px-3 py-4 align-middle whitespace-nowrap">
                                <x-work-order-status-badge :status="$w->status" />
                            </td>
                            <td class="px-3 py-4 align-middle">
                                @if ($w->assignee)
                                    <span class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-[11px] font-bold text-navy flex-shrink-0">{{ $w->assignee->initialsOrGenerated() }}</span>
                                        <span class="text-[13.5px] truncate max-w-[130px]" title="{{ $w->assignee->name }}">{{ $w->assignee->name }}</span>
                                    </span>
                                @else
                                    <span class="text-[13px] text-[#A09A8C]">— à affecter</span>
                                @endif
                            </td>
                            <td class="pl-3 pr-6 py-4 align-middle">
                                <div class="font-mono text-[12px] whitespace-nowrap {{ \App\Support\Swatch::text($slaColor) }}">{{ $sla['text'] }}</div>
                                @if ($sla['width'] > 0)
                                    <div class="mt-1.5 h-[4px] rounded-full bg-line-soft overflow-hidden">
                                        <div class="h-full {{ \App\Support\Swatch::bg($slaColor) }}" style="width: {{ $sla['width'] }}%"></div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="px-6 py-3.5 border-t border-line bg-paper/60 text-right">
        <a href="{{ route('work-orders.index') }}" class="text-[13px] font-semibold text-navy hover:underline">Voir tous les ordres →</a>
    </div>
</section>
