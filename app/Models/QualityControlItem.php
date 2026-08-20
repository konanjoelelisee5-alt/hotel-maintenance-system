<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityControlItem extends Model
{
    protected $table = 'quality_control_items';

    protected $fillable = ['quality_control_id', 'label', 'is_compliant', 'comment'];

    protected $casts = [
        'is_compliant' => 'boolean',
    ];

    public function qualityControl()
    {
        return $this->belongsTo(WorkOrderQualityControl::class, 'quality_control_id');
    }
}