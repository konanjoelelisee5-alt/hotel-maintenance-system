<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use LogsConfigurationChanges;

    const LOG_PREFIX = 'skill';
    const LOG_LABEL = 'la compétence';

    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}