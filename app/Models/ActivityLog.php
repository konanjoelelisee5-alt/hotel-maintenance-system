<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\SensitiveAdminActionNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    /**
     * Actions dont les autres administrateurs sont prévenus (contrôle mutuel).
     * Un "*" final couvre toutes les actions d'un préfixe.
     */
    private const SENSITIVE_ACTIONS = [
        'user.role_changed',
        'user.department_head_changed',
        'user.alerts_changed',
        'user.deactivated',
        'user.reactivated',
        'user.password_reset',
        'user.admin_recovered',
        'sla_policy.*',
        'escalation_rule.*',
        'setting.*',
    ];

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
        $log = static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ]);

        if ($log->isSensitive()) {
            $log->notifyOtherAdmins();
        }
    }

    public function isSensitive(): bool
    {
        // La création d'un compte n'est sensible que si elle crée un administrateur.
        if ($this->action === 'user.created') {
            return ($this->metadata['role'] ?? null) === UserRole::Admin->value;
        }

        return Str::is(self::SENSITIVE_ACTIONS, $this->action);
    }

    private function notifyOtherAdmins(): void
    {
        $admins = User::where('role', UserRole::Admin)
            ->where('is_active', true)
            ->when($this->user_id, fn ($q) => $q->whereKeyNot($this->user_id))
            ->get();

        Notification::send($admins, new SensitiveAdminActionNotification($this));
    }
}
