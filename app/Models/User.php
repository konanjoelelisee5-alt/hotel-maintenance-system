<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Libellé lisible du rôle (pour l'affichage dans les vues).
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'        => 'Administrateur',
            'manager'      => 'Manager',
            'technicien'   => 'Technicien',
            'housekeeping' => 'Housekeeping',
            'reception'    => 'Réception',
            default        => 'Utilisateur',
        };
    }

    /**
     * Nom de la route du dashboard correspondant au rôle.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            'admin'        => 'admin.dashboard',
            'manager'      => 'manager.dashboard',
            'technicien'   => 'technicien.dashboard',
            'housekeeping' => 'housekeeping.dashboard',
            'reception'    => 'reception.dashboard',
            default        => 'login',
        };
    }

    // ===== Relations (Module B) =====
    public function assignedWorkOrders()
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    public function reportedWorkOrders()
    {
        return $this->hasMany(WorkOrder::class, 'reported_by');
    }

    // ===== Relations (Module C — Planification) =====
    public function skills()
    {
        return $this->belongsToMany(Skill::class);
    }

    public function availabilities()
    {
        return $this->hasMany(TechnicianAvailability::class);
    }

    public function scheduledWorkOrders()
    {
        return $this->hasMany(WorkOrder::class, 'scheduled_by');
    }

    /**
     * Vérifie si le technicien est en congé/absence à une date donnée (indépendamment de l'heure).
     */
    public function isOnLeaveOn(\Carbon\Carbon $date): bool
    {
        return $this->availabilities()
            ->whereIn('type', ['conge', 'absence'])
            ->whereDate('date_start', '<=', $date->toDateString())
            ->whereDate('date_end', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * Vérifie si le technicien est disponible à une date/heure donnée.
     */
    public function isAvailableAt(\Carbon\Carbon $dateTime): bool
    {
        // 1. Vérifier qu'il n'est pas en congé/absence à cette date
        if ($this->isOnLeaveOn($dateTime)) {
            return false;
        }

        // 2. Vérifier qu'il a un créneau récurrent disponible ce jour-là à cette heure
        $hasSlot = $this->availabilities()
            ->where('type', 'disponible')
            ->where('day_of_week', $dateTime->dayOfWeek)
            ->whereTime('start_time', '<=', $dateTime->format('H:i:s'))
            ->whereTime('end_time', '>=', $dateTime->format('H:i:s'))
            ->exists();

        return $hasSlot;
    }

    public function interventionSessions()
    {
        return $this->hasMany(InterventionSession::class, 'technician_id');
    }

    public function interventionReports()
    {
        return $this->hasMany(InterventionReport::class, 'technician_id');
    }

    public function createdPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function reviewedQualityControls()
    {
        return $this->hasMany(WorkOrderQualityControl::class, 'reviewed_by');
    }

    public function requestedCorrections()
    {
        return $this->hasMany(CorrectionRequest::class, 'requested_by');
    }
}