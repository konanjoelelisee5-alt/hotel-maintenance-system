<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Passage de la gouvernante dans une chambre : chaque point est noté conforme,
 * non conforme ou sans objet ; les non-conformités deviennent des OT à la fin.
 */
class RoomInspection extends Model
{
    public const IN_PROGRESS = 'en_cours';

    public const DONE = 'terminee';

    protected $fillable = ['room_id', 'inspector_id', 'status', 'notes', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items()
    {
        return $this->hasMany(RoomInspectionItem::class)->orderBy('id');
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', self::DONE);
    }

    public function isDone(): bool
    {
        return $this->status === self::DONE;
    }

    /** Points notés / points à noter. */
    public function progress(): array
    {
        $answered = $this->items->whereNotNull('result')->count();

        return ['answered' => $answered, 'total' => $this->items->count()];
    }

    /** Conformité en % des points vérifiés (les « sans objet » ne comptent pas). */
    public function conformity(): ?int
    {
        $checked = $this->items->whereIn('result', ['ok', 'nok']);

        return $checked->isEmpty() ? null : (int) round($checked->where('result', 'ok')->count() / $checked->count() * 100);
    }
}
