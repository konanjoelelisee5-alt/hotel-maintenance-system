<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionReport extends Model
{
    protected $fillable = [
        'work_order_id', 'technician_id',
        'work_performed', 'parts_used', 'recommendations',
        'signature_path', 'signed_by_name', 'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function getIsSignedAttribute(): bool
    {
        return ! is_null($this->signature_path);
    }
}