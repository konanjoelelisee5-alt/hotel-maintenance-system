<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    protected $fillable = [
        'work_order_id', 'quality_control_id', 'requested_by',
        'description', 'status', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function qualityControl()
    {
        return $this->belongsTo(WorkOrderQualityControl::class, 'quality_control_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ouverte' => 'Ouverte',
            'traitee' => 'Traitée',
            default => $this->status,
        };
    }
}