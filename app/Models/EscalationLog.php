<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EscalationLog extends Model
{
    protected $fillable = [
        'work_order_id', 'escalation_rule_id', 'notified_user_id',
        'triggered_at', 'message',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function rule()
    {
        return $this->belongsTo(EscalationRule::class, 'escalation_rule_id');
    }

    public function notifiedUser()
    {
        return $this->belongsTo(User::class, 'notified_user_id');
    }
}