<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; margin-bottom: 5px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; }
        .kpi-grid { width: 100%; margin-bottom: 20px; }
        .kpi-box { display: inline-block; width: 23%; padding: 10px; border: 1px solid #ddd; margin-right: 1%; }
        .kpi-label { font-size: 10px; color: #888; }
        .kpi-value { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Rapport d'activité — Service technique</h1>
    <p class="subtitle">Généré le {{ now()->format('d/m/Y à H:i') }}</p>

    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="kpi-label">Total OT</div>
            <div class="kpi-value">{{ $totalCount }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Temps moyen résolution</div>
            <div class="kpi-value">{{ $avgResolutionHours }} h</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Taux respect SLA</div>
            <div class="kpi-value">{{ $slaRate !== null ? $slaRate . ' %' : '—' }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-label">Coût achats</div>
            <div class="kpi-value">{{ number_format($totalPurchaseCost, 2) }} €</div>
        </div>
    </div>

    <h3>Répartition par statut</h3>
    <table>
        <thead>
            <tr><th>Statut</th><th>Nombre</th></tr>
        </thead>
        <tbody>
            @foreach ($byStatus as $status => $count)
                <tr><td>{{ $status }}</td><td>{{ $count }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 20px;">Détail des ordres de travail</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Titre</th><th>Statut</th><th>Assigné à</th><th>Créé le</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($workOrders as $wo)
                <tr>
                    <td>{{ $wo->id }}</td>
                    <td>{{ $wo->title }}</td>
                    <td>{{ $wo->status_label }}</td>
                    <td>{{ $wo->assignee?->name ?? '—' }}</td>
                    <td>{{ $wo->created_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>