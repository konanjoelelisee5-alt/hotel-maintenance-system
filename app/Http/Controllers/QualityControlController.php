<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewQualityControlRequest;
use App\Http\Requests\StoreQualityControlRequest;
use App\Models\ChecklistTemplate;
use App\Models\CorrectionRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderQualityControl;
use App\Notifications\QualityControlNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\ActivityLog;

class QualityControlController extends Controller
{
    public function create(WorkOrder $workOrder): View
    {
        $templates = ChecklistTemplate::query()
            ->where(function ($query) use ($workOrder) {
                $query->whereNull('work_order_type')->orWhere('work_order_type', $workOrder->type->code);
            })
            ->get();

        return view('quality-controls.create', compact('workOrder', 'templates'));
    }

    public function store(StoreQualityControlRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $qualityControl = DB::transaction(function () use ($request, $workOrder) {
            $qc = $workOrder->qualityControls()->create([
                'checklist_template_id' => $request->validated('checklist_template_id'),
                'reviewed_by' => Auth::id(),
            ]);

            if ($templateId = $request->validated('checklist_template_id')) {
                $qc->populateFromTemplate(ChecklistTemplate::findOrFail($templateId));
            }

            return $qc;
        });

        return redirect()->route('quality-controls.show', $qualityControl)
            ->with('success', 'Contrôle qualité démarré.');
    }

    public function show(WorkOrderQualityControl $qualityControl): View
    {
        $qualityControl->load(['workOrder', 'items', 'reviewer']);

        return view('quality-controls.show', compact('qualityControl'));
    }

    public function review(ReviewQualityControlRequest $request, WorkOrderQualityControl $qualityControl): RedirectResponse
    {
        DB::transaction(function () use ($request, $qualityControl) {
            foreach ($request->validated('items', []) as $itemId => $itemData) {
                $qualityControl->items()->where('id', $itemId)->update([
                    'is_compliant' => $itemData['is_compliant'] ?? null,
                    'comment' => $itemData['comment'] ?? null,
                ]);
            }

            $decision = $request->validated('decision');
            $comment = $request->validated('overall_comment');

            $qualityControl->update([
                'status' => $decision,
                'overall_comment' => $comment,
                'reviewed_at' => now(),
            ]);

            $workOrder = $qualityControl->workOrder;

            if ($decision === 'approuve') {
                $workOrder->update(['status' => 'ferme']);

                ActivityLog::record('quality_control.approved', "Contrôle qualité approuvé pour l'OT #{$workOrder->id}", $workOrder);

                $workOrder->statusHistories()->create([
                    'changed_by' => Auth::id(),
                    'old_status' => 'resolu',
                    'new_status' => 'ferme',
                    'note' => 'Fermeture suite à validation du contrôle qualité.',
                ]);

                if ($workOrder->reporter) {
                    $workOrder->reporter->notify(new QualityControlNotification($workOrder, 'approuve'));
                }
            } else {
                $workOrder->update(['status' => 'rejete']);

                ActivityLog::record('quality_control.rejected', "Contrôle qualité rejeté pour l'OT #{$workOrder->id} : {$comment}", $workOrder);

                $workOrder->statusHistories()->create([
                    'changed_by' => Auth::id(),
                    'old_status' => 'resolu',
                    'new_status' => 'rejete',
                    'note' => $comment,
                ]);

                CorrectionRequest::create([
                    'work_order_id' => $workOrder->id,
                    'quality_control_id' => $qualityControl->id,
                    'requested_by' => Auth::id(),
                    'description' => $comment,
                ]);

                if ($workOrder->assignee) {
                    $workOrder->assignee->notify(new QualityControlNotification($workOrder, 'rejete', $comment));
                }
            }
        });

        return redirect()->route('work-orders.show', $qualityControl->workOrder)
            ->with('success', 'Contrôle qualité enregistré.');
    }
}