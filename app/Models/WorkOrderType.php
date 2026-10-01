<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Model;

class WorkOrderType extends Model
{
    use LogsConfigurationChanges;

    const LOG_PREFIX = 'work_order_type';
    const LOG_LABEL = "le type d'OT";

    protected $fillable = ['code', 'label', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'type_id');
    }
}