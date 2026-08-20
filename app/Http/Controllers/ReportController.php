<?php

namespace App\Http\Controllers;

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

        $callback = function () use ($workOrders) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['ID', 'Titre', 'Statut', 'Priorité', 'Assigné à', 'Créé le', 'Résolu le', 'SLA dépassé'], ';');

            foreach ($workOrders as $wo) {
                fputcsv($file, [
                    $wo->id,
                    $wo->title,
                    $wo->status_label,
                    $wo->priority->label,
                    $wo->assignee?->name ?? 'Non assigné',
                    $wo->created_at->format('d/m/Y H:i'),
                    $wo->completed_at?->format('d/m/Y H:i') ?? '—',
                    $wo->sla_breached ? 'Oui' : 'Non',
                ], ';');
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

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->download('rapport-' . now()->format('Y-m-d') . '.pdf');
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
        $workOrders = $this->filteredWorkOrders($request)->with('priority')->get();
        $resolvedOrders = $workOrders->whereNotNull('completed_at');

        $avgResolutionHours = $resolvedOrders->isNotEmpty()
            ? round($resolvedOrders->avg(fn ($wo) => $wo->created_at->diffInMinutes($wo->completed_at)) / 60, 1)
            : 0;

        $slaEligible = $workOrders->whereNotNull('sla_resolution_due_at');
        $slaRespected = $slaEligible->where('sla_breached', false)->count();
        $slaRate = $slaEligible->isNotEmpty() ? round(($slaRespected / $slaEligible->count()) * 100, 1) : null;

        $byStatus = $workOrders->groupBy('status')->map->count();

        $byTechnician = $workOrders->whereNotNull('assigned_to')
            ->groupBy(fn ($wo) => $wo->assignee?->name ?? 'Inconnu')
            ->map->count();

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
            'slaRate' => $slaRate,
            'byStatus' => $byStatus,
            'byTechnician' => $byTechnician,
            'totalPurchaseCost' => $totalPurchaseCost,
            'qualityRejectionRate' => $qualityRejectionRate,
            'filters' => $request->only(['date_from', 'date_to', 'technician_id', 'type_id']),
        ];
    }
}