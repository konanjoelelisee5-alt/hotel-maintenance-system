<?php

namespace App\Support;

/**
 * Durée lisible pour les fiches : « 45 min », « 3 h 20 », « 42 j 14 h ».
 * (« 2000 min » ou « 1022h56 » ne se lisent pas d'un coup d'œil.)
 */
class Duration
{
    public static function human(int|float|null $minutes): string
    {
        $minutes = (int) floor(max(0, (float) $minutes));

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 48) {
            $rest = $minutes % 60;

            return $rest ? sprintf('%d h %02d', $hours, $rest) : "{$hours} h";
        }

        $days = intdiv($hours, 24);
        $restHours = $hours % 24;

        return $restHours ? "{$days} j {$restHours} h" : "{$days} j";
    }
}
