<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderType extends Model
{
    protected $fillable = ['code', 'label', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'type_id');
    }
}