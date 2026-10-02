<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderQualityControl;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildReportData($request);
        $technicians = User::where('role', 'technicien')->orderBy('name')->get();

        return view('reports.index', array_merge($data, compact('technicians')));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $workOrders = $this->filteredWorkOrders($request)->with(['assignee', 'room', 'priority'])->get();
        $this->logExport('CSV', $request, $workOrders->count());

        $callback = function () use ($workOrders) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['ID', 'Titre', 'Statut', 'Priorité', 'Assigné à', 'Créé le', 'Résolu le', 'SLA dépassé'], ';');

            foreach ($workOrders as $wo) {
                fputcsv($file, array_map(self::csvCell(...), [
                    $wo->id,
                    $wo->title,
                    $wo->status_label,
                    $wo->priority->label,
                    $wo->assignee?->name ?? 'Non assigné',
                    $wo->created_at->format('d/m/Y H:i'),
                    $wo->completed_at?->format('d/m/Y H:i') ?? '—',
                    $wo->sla_breached ? 'Oui' : 'Non',
                ]), ';');
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'rapport-ot-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $data = $this->buildReportData($request);
        $this->logExport('PDF', $request, $data['totalCount']);

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download('rapport-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Un titre d'OT est saisi par n'importe quel employé : « =HYPERLINK(...) » ou
     * « =1+1 » serait exécuté comme formule par Excel à l'ouverture du CSV.
     * L'apostrophe en tête le fait lire comme du texte.
     */
    private static function csvCell(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** Les données qui quittent l'application (fichier envoyé, imprimé...) sont tracées. */
    private function logExport(string $format, Request $request, int $count): void
    {
        $filters = array_filter($request->only(['date_from', 'date_to', 'technician_id', 'type_id']), 'filled');

        ActivityLog::record(
            'report.exported',
            "Export {$format} du rapport des OT ({$count} ordre(s))"
                .($filters ? ' — filtres : '.collect($filters)->map(fn ($v, $k) => "{$k}={$v}")->implode(', ') : ''),
            null,
            ['format' => $format, 'count' => $count, 'filters' => $filters],
        );
    }

    private function filteredWorkOrders(Request $request)
    {
        return WorkOrder::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('technician_id'), fn ($q) => $q->where('assigned_to', $request->technician_id))
            ->when($request->filled('type_id'), fn ($q) => $q->where('type_id', $request->type_id));
    }

    private function buildReportData(Request $request): array
    {
        $workOrders = $this->filteredWorkOrders($request)->with(['priority', 'assignee'])->get();
        $resolvedOrders = $workOrders->whereNotNull('completed_at');

        $avgResolutionMinutes = $resolvedOrders->isNotEmpty()
            ? (int) round($resolvedOrders->avg(fn ($wo) => $wo->created_at->diffInMinutes($wo->completed_at)))
            : null;
        $avgResolutionHours = $avgResolutionMinutes !== null ? round($avgResolutionMinutes / 60, 1) : 0;

        // Un OT annulé ne compte pas. En retard : signalé dépassé, terminé après son
        // échéance, ou pas terminé alors que l'échéance est passée (sans attendre que la
        // tâche planifiée ait posé sla_breached).
        $slaEligible = $workOrders->whereNotNull('sla_resolution_due_at')->where('status', '!=', 'annule');
        $slaRespected = $slaEligible->reject(fn (WorkOrder $wo) => $wo->sla_breached
            || ($wo->completed_at
                ? $wo->completed_at->greaterThan($wo->sla_resolution_due_at)
                : ! in_array($wo->status, WorkOrder::FINISHED_STATUSES, true) && $wo->sla_resolution_due_at->isPast())
        )->count();
        $slaRate = $slaEligible->isNotEmpty() ? round(($slaRespected / $slaEligible->count()) * 100, 1) : null;

        // Statuts dans l'ordre du cycle de vie, avec leur libellé (« En cours », pas « en_cours »).
        $counts = $workOrders->countBy('status');
        $byStatus = collect(WorkOrder::STATUS_LABELS)
            ->filter(fn ($label, $status) => $counts->has($status))
            ->mapWithKeys(fn ($label, $status) => [$label => $counts[$status]]);

        $byTechnician = $workOrders->whereNotNull('assigned_to')
            ->groupBy(fn ($wo) => $wo->assignee?->name ?? 'Inconnu')
            ->map->count()
            ->sortDesc();

        $totalPurchaseCost = PurchaseOrder::query()
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->sum('total_amount');

        $qualityControls = WorkOrderQualityControl::whereIn('work_order_id', $workOrders->pluck('id'))->get();
        $qualityRejectionRate = $qualityControls->isNotEmpty()
            ? round(($qualityControls->where('status', 'rejete')->count() / $qualityControls->count()) * 100, 1)
            : null;

        return [
            'workOrders' => $workOrders,
            'totalCount' => $workOrders->count(),
            'avgResolutionHours' => $avgResolutionHours,
            'avgResolutionMinutes' => $avgResolutionMinutes,
            'openCount' => $workOrders->whereIn('status', ['ouvert', 'en_cours', 'en_attente'])->count(),
            'resolvedCount' => $resolvedOrders->count(),
            'slaEligibleCount' => $slaEligible->count(),
            'slaRate' => $slaRate,
            'byStatus' => $byStatus,
            'byTechnician' => $byTechnician,
            'totalPurchaseCost' => $totalPurchaseCost,
            'qualityRejectionRate' => $qualityRejectionRate,
            'filters' => $request->only(['date_from', 'date_to', 'technician_id', 'type_id']),
        ];
    }
}