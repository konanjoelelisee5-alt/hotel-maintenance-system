<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Le contrat MustVerifyEmail était importé mais jamais implémenté : les comptes
// (créés vérifiés d'office, cf. UserController et les factories) fonctionnaient
// par accident, mais hasVerifiedEmail()/markEmailAsVerified() plantaient dès
// qu'une route Breeze de vérification d'e-mail (/verify-email) était atteinte.
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_department_head',
        'receives_maintenance_alerts',
        'is_active',
        'must_change_password',
    ];

    // Miroir des valeurs par défaut des colonnes : sans cela, un modèle tout juste
    // créé aurait is_active à null en mémoire, et serait vu comme désactivé.
    protected $attributes = [
        'is_department_head' => false,
        'receives_maintenance_alerts' => true,
        'is_active' => true,
        'must_change_password' => false,
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
            'is_department_head' => 'boolean',
            'receives_maintenance_alerts' => 'boolean',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    /**
     * Vrai si ce compte est le seul administrateur actif : le rétrograder ou le
     * désactiver laisserait l'application sans personne pour la paramétrer.
     */
    public function isLastActiveAdmin(): bool
    {
        return $this->role === UserRole::Admin
            && $this->is_active
            && ! static::where('role', UserRole::Admin)
                ->where('is_active', true)
                ->whereKeyNot($this->id)
                ->exists();
    }

    /**
     * Libellé lisible du rôle (pour l'affichage dans les vues).
     */
    public function getRoleLabelAttribute(): string
    {
        $label = $this->role?->label() ?? 'Utilisateur';

        return $this->isDepartmentHead() ? "Responsable {$label}" : $label;
    }

    /**
     * Responsable de son service : l'indicateur n'a de sens que pour les services
     * qui en ont un (housekeeping, réception), il est ignoré pour les autres rôles.
     */
    public function isDepartmentHead(): bool
    {
        return $this->is_department_head && (bool) $this->role?->hasDepartmentHead();
    }

    /**
     * Nom de la route du dashboard correspondant au rôle.
     */
    public function dashboardRoute(): string
    {
        return $this->role?->dashboardRoute() ?? 'login';
    }

    /**
     * Destinataire du canal "alerte téléphone" (cf. PhoneAlertChannel).
     */
    public function routeNotificationForPhoneAlert(): ?string
    {
        return $this->phone;
    }

    /**
     * Techniciens à qui l'on peut confier du travail. Avec $keepId, garde aussi
     * l'assigné actuel d'un OT même s'il a quitté l'hôtel (affichage d'un vieil OT).
     */
    public function scopeActiveTechnicians($query, ?int $keepId = null)
    {
        return $query->where('role', UserRole::Technicien)
            // orWhereKey() n'existe pas dans Eloquent : la clé est comparée explicitement.
            ->where(fn ($q) => $q->where('is_active', true)->when($keepId, fn ($q) => $q->orWhere($q->getModel()->getQualifiedKeyName(), $keepId)))
            ->orderBy('name');
    }

    /**
     * Admins et managers qui pilotent la maintenance : seuls eux reçoivent les
     * alertes d'astreinte et d'escalade (pas le responsable informatique, par ex.).
     */
    public function scopeMaintenanceAlertRecipients($query, UserRole $role)
    {
        return $query->where('role', $role)
            ->where('is_active', true)
            ->where('receives_maintenance_alerts', true);
    }

    /**
     * Initiales affichées dans les avatars (sidebar, cloche de notifications...).
     */
    public function initialsOrGenerated(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $initials = collect($parts)->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');

        return $initials !== '' ? $initials : '?';
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