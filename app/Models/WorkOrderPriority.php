<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Model;

class WorkOrderPriority extends Model
{
    use LogsConfigurationChanges;

    const LOG_PREFIX = 'work_order_priority';
    const LOG_LABEL = 'la priorité';

    protected $fillable = ['code', 'label', 'color', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'priority_id');
    }
}