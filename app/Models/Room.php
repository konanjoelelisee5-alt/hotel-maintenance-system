<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'floor', 'status'];

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    public function maintenancePlans()
    {
        return $this->hasMany(MaintenancePlan::class);
    }
}