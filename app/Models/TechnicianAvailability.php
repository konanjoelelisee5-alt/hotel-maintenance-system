<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianAvailability extends Model
{
    protected $fillable = [
        'user_id', 'type', 'day_of_week', 'start_time', 'end_time',
        'date_start', 'date_end', 'note',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
