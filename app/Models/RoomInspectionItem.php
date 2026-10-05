<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un point vérifié pendant une inspection de chambre. */
class RoomInspectionItem extends Model
{
    protected $fillable = [
        'room_inspection_id', 'point_key', 'zone', 'label', 'category',
        'result', 'comment', 'photo_path', 'work_order_id',
    ];

    public function inspection()
    {
        return $this->belongsTo(RoomInspection::class, 'room_inspection_id');
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
