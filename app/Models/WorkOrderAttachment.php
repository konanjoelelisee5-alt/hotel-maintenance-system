<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderAttachment extends Model
{
    // Suppression douce : le fichier reste sur le disque comme preuve (cf. migration).
    use SoftDeletes;

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

    /** Lien protégé (droits vérifiés à chaque ouverture), jamais l'adresse du fichier. */
    public function getUrlAttribute(): string
    {
        return route('work-orders.attachments.show', [$this->work_order_id, $this->id]);
    }
}