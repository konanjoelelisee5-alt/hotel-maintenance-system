<?php

namespace App\Models;

use App\Models\Concerns\LogsConfigurationChanges;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use LogsConfigurationChanges;

    const LOG_PREFIX = 'setting';
    const LOG_LABEL = 'le paramètre';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        // firstOrNew + save (et non updateOrCreate en masse) pour déclencher
        // les évènements du modèle, donc la journalisation.
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->save();
    }
}
