<?php

namespace App\Support;

/**
 * Fait correspondre les clés de couleur sémantique utilisées dans les contrôleurs/vues
 * (red/amber/gold/green/blue/grey) à des classes Tailwind littérales, pour que le
 * scanner JIT de Tailwind les détecte (il ne suit pas les chaînes construites dynamiquement).
 */
class Swatch
{
    private const MAP = [
        'red' => ['bg-red', 'text-red', 'bg-[#FDECEA]'],
        'amber' => ['bg-amber', 'text-amber', 'bg-[#FBF1DF]'],
        'gold' => ['bg-gold', 'text-gold', 'bg-[#FBF1DF]'],
        'green' => ['bg-green', 'text-green', 'bg-[#E6F3EC]'],
        'blue' => ['bg-blue', 'text-blue', 'bg-[#EAF0F6]'],
        'grey' => ['bg-[#8A8578]', 'text-[#6C6658]', 'bg-line-soft'],
    ];

    public static function bg(?string $key): string
    {
        return self::MAP[$key][0] ?? self::MAP['grey'][0];
    }

    public static function text(?string $key): string
    {
        return self::MAP[$key][1] ?? self::MAP['grey'][1];
    }

    public static function soft(?string $key): string
    {
        return self::MAP[$key][2] ?? self::MAP['grey'][2];
    }
}
