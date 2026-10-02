<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    {{-- DomPDF : mise en page en tableaux (pas de flex/grid), police DejaVu (accents, espaces insécables). --}}
    <style>
        @page { margin: 28px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #14202B; }
        .header { border-bottom: 3px solid #B58435; padding-bottom: 10px; margin-bottom: 14px; }
        .header img { height: 40px; float: left; margin-right: 12px; }
        .header h1 { font-size: 16px; color: #0E2136; margin: 2px 0 2px; }
        .header .meta { color: #6C6658; font-size: 10px; }
        .filters { background: #FAF8F4; border: 1px solid #E2DCD0; padding: 7px 10px; margin-bottom: 14px; color: #4A4639; }
        table { width: 100%; border-collapse: collapse; }
        .kpis td { width: 20%; border: 1px solid #E2DCD0; padding: 9px 10px; vertical-align: top; }
        .kpi-label { font-size: 9px; color: #6C6658; text-transform: uppercase; letter-spacing: .3px; }
        .kpi-value { font-size: 16px; font-weight: bold; color: #0E2136; margin-top: 3px; }
        .kpi-sub { font-size: 9px; color: #8A8578; margin-top: 2px; }
        h2 { font-size: 12px; color: #0E2136; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #E2DCD0; }
        .list th { background: #0E2136; color: #fff; font-weight: bold; text-align: left; padding: 6px 7px; font-size: 9.5px; }
        .list td { border-bottom: 1px solid #F3EFE6; padding: 5px 7px; }
        .list tr:nth-child(even) td { background: #FAF8F4; }
        .num { text-align: right; }
        .red { color: #B3261E; font-weight: bold; }
        .footer { position: fixed; bottom: -14px; left: 0; right: 0; font-size: 8.5px; color: #8A8578; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/logo-hotel-president-icon.jpg') }}" alt="Hôtel Président">
        <h1>Rapport d'activité du service technique</h1>
        <div class="meta">Hôtel Président · généré le {{ now()->format('d/m/Y à H\hi') }}</div>
        <div style="clear: both"></div>
    </div>

    <div class="filters">
        Période :
        <strong>
            @if (! empty($filters['date_from']) || ! empty($filters['date_to']))
                {{ ! empty($filters['date_from']) ? 'du '.\Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : '' }}
                {{ ! empty($filters['date_to']) ? 'au '.\Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : '' }}
            @else
                toute la période
            @endif
        </strong>
        @if (! empty($filters['technician_id']))
            · Technicien : <strong>{{ \App\Models\User::find($filters['technician_id'])?->name }}</strong>
        @endif
        @if (! empty($filters['type_id']))
            · Type : <strong>{{ \App\Models\WorkOrderType::find($filters['type_id'])?->label }}</strong>
        @endif
    </div>

    <table class="kpis">
        <tr>
            <td>
                <div class="kpi-label">Ordres de travail</div>
                <div class="kpi-value">{{ $totalCount }}</div>
                <div class="kpi-sub">{{ $openCount }} ouvert(s) · {{ $resolvedCount }} terminé(s)</div>
            </td>
            <td>
                <div class="kpi-label">Temps moyen de résolution</div>
                <div class="kpi-value">{{ $avgResolutionMinutes !== null ? \App\Support\Duration::human($avgResolutionMinutes) : '—' }}</div>
                <div class="kpi-sub">création → réparation</div>
            </td>
            <td>
                <div class="kpi-label">Respect du SLA</div>
                <div class="kpi-value {{ $slaRate !== null && $slaRate < 75 ? 'red' : '' }}">{{ $slaRate !== null ? $slaRate.' %' : '—' }}</div>
                <div class="kpi-sub">sur {{ $slaEligibleCount }} OT avec délai</div>
            </td>
            <td>
                <div class="kpi-label">Rejets qualité</div>
                <div class="kpi-value">{{ $qualityRejectionRate !== null ? $qualityRejectionRate.' %' : '—' }}</div>
                <div class="kpi-sub">des contrôles réalisés</div>
            </td>
            <td>
                <div class="kpi-label">Coût des achats</div>
                <div class="kpi-value">{{ \App\Support\Money::format($totalPurchaseCost) }}</div>
                <div class="kpi-sub">bons de commande</div>
            </td>
        </tr>
    </table>

    <h2>Répartition par statut</h2>
    <table class="list">
        <thead><tr><th>Statut</th><th class="num">Nombre</th><th class="num">Part</th></tr></thead>
        <tbody>
            @forelse ($byStatus as $label => $count)
                <tr><td>{{ $label }}</td><td class="num">{{ $count }}</td><td class="num">{{ round($count / max($totalCount, 1) * 100) }} %</td></tr>
            @empty
                <tr><td colspan="3">Aucun ordre de travail pour ces filtres.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($byTechnician->isNotEmpty())
        <h2>Charge par technicien</h2>
        <table class="list">
            <thead><tr><th>Technicien</th><th class="num">OT affectés</th></tr></thead>
            <tbody>
                @foreach ($byTechnician as $name => $count)
                    <tr><td>{{ $name }}</td><td class="num">{{ $count }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Détail des ordres de travail</h2>
    <table class="list">
        <thead>
            <tr><th>Réf.</th><th>Titre</th><th>Statut</th><th>Priorité</th><th>Technicien</th><th>Créé le</th><th>SLA</th></tr>
        </thead>
        <tbody>
            @foreach ($workOrders as $wo)
                <tr>
                    <td>{{ $wo->code() }}</td>
                    <td>{{ $wo->title }}</td>
                    <td>{{ $wo->status_label }}</td>
                    <td>{{ $wo->priority->label }}</td>
                    <td>{{ $wo->assignee?->name ?? '—' }}</td>
                    <td>{{ $wo->created_at->format('d/m/Y') }}</td>
                    @php
                        $sla = match (true) {
                            ! $wo->sla_resolution_due_at => '—',
                            $wo->sla_breached => 'Dépassé',
                            in_array($wo->status, \App\Models\WorkOrder::FINISHED_STATUSES, true) => 'Respecté',
                            default => 'En cours',
                        };
                    @endphp
                    <td class="{{ $wo->sla_breached ? 'red' : '' }}">{{ $sla }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Hôtel Président — Service technique · Montants en francs CFA (FCFA)</div>
</body>
</html>
