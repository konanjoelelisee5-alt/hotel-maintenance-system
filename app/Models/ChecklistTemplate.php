<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    protected $fillable = ['name', 'work_order_type', 'description'];

    public function items()
    {
        return $this->hasMany(ChecklistTemplateItem::class)->orderBy('position');
    }
}