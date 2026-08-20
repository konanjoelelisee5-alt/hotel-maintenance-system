<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = ['name', 'type', 'room_id', 'status'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function maintenancePlans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }
}