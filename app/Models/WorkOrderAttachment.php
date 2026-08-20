<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WorkOrderAttachment extends Model
{
    protected $fillable = [
        'work_order_id', 'uploaded_by', 'file_path',
        'original_name', 'mime_type', 'size',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }
}