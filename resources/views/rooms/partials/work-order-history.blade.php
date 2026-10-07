{{-- Historique des OT d'un lieu ou d'un équipement (le « carnet de santé »). --}}
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <h3 class="font-semibold text-ink-deep px-6 pt-5 pb-3">Historique des ordres de travail</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-paper">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">OT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Priorité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-ink-muted uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($workOrders as $w)
                    <tr class="hover:bg-paper">
                        <td class="px-6 py-3 text-sm font-mono">
                            <a href="{{ route('work-orders.show', $w) }}" class="text-blue hover:underline">{{ $w->code() }}</a>
                        </td>
                        <td class="px-6 py-3 text-sm text-navy">{{ $w->title }}</td>
                        <td class="px-6 py-3 text-sm text-ink-muted">{{ $w->priority?->label ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm text-ink-muted">{{ $w->status_label }}</td>
                        <td class="px-6 py-3 text-sm text-ink-muted whitespace-nowrap">{{ $w->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-ink-muted">Aucun ordre de travail pour l'instant.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($workOrders->hasPages())
        <div class="px-6 py-3 border-t border-line-soft">{{ $workOrders->links() }}</div>
    @endif
</div>
