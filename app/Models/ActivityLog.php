<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Méthode centrale pour enregistrer une action, utilisée partout dans l'application.
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $metadata = []): void
    {
        static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ]);
    }
}