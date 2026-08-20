<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderQualityControl extends Model
{
    protected $fillable = [
        'work_order_id', 'checklist_template_id', 'reviewed_by',
        'status', 'overall_comment', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items()
    {
        return $this->hasMany(QualityControlItem::class, 'quality_control_id');
    }

    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    /**
     * Duplique les points du modèle de checklist choisi vers ce contrôle qualité,
     * en "figeant" le texte de chaque point tel qu'il est au moment de la duplication.
     */
    public function populateFromTemplate(ChecklistTemplate $template): void
    {
        foreach ($template->items as $templateItem) {
            $this->items()->create([
                'label' => $templateItem->label,
            ]);
        }
    }

    /**
     * Tous les points ont-ils été évalués (aucun n'est resté à null) ?
     */
    public function getIsFullyEvaluatedAttribute(): bool
    {
        return $this->items->every(fn (QualityControlItem $item) => ! is_null($item->is_compliant));
    }

    /**
     * Y a-t-il au moins un point jugé non conforme ?
     */
    public function getHasNonComplianceAttribute(): bool
    {
        return $this->items->contains(fn (QualityControlItem $item) => $item->is_compliant === false);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'en_attente' => 'En attente',
            'approuve' => 'Approuvé',
            'rejete' => 'Rejeté',
            default => $this->status,
        };
    }
}