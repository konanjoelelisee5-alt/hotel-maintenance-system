<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrderStatusHistory extends Model
{
    protected $fillable = ['work_order_id', 'changed_by', 'old_status', 'new_status', 'note'];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}