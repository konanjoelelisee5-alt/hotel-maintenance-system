<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderPriority extends Model
{
    protected $fillable = ['code', 'label', 'color', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'priority_id');
    }
}