<?php

namespace App\Support;

/**
 * Fait correspondre les clés de couleur sémantique utilisées dans les contrôleurs/vues
 * (red/amber/gold/green/blue/navy/grey/muted) à des classes Tailwind littérales, pour que
 * le scanner JIT de Tailwind les détecte (il ne suit pas les chaînes construites dynamiquement).
 *
 * Colonnes : [fond plein, texte, fond doux, pastille (fond doux + texte foncé lisible), hex].
 * La pastille garde un contraste AA : le texte « plein » sur fond doux ne l'atteint pas
 * pour l'or et l'ambre.
 */
class Swatch
{
    private const MAP = [
        'red' => ['bg-red', 'text-red', 'bg-[#FDECEA]', 'bg-[#FDECEA] text-[#8A1F16]', '#B3261E'],
        'amber' => ['bg-amber', 'text-amber', 'bg-[#FBF1DF]', 'bg-[#FBF1DF] text-[#7A5A16]', '#B4740F'],
        'gold' => ['bg-gold', 'text-gold', 'bg-[#FBF1DF]', 'bg-gold-100 text-gold-700', '#B58435'],
        'green' => ['bg-green', 'text-green', 'bg-[#E6F3EC]', 'bg-[#E6F3EC] text-[#155C40]', '#1E7A55'],
        'blue' => ['bg-blue', 'text-blue', 'bg-[#EAF0F6]', 'bg-[#EAF0F6] text-blue', '#26496B'],
        'navy' => ['bg-navy', 'text-navy', 'bg-[#EAF0F6]', 'bg-[#EAF0F6] text-navy', '#0E2136'],
        'grey' => ['bg-[#8A8578]', 'text-[#6C6658]', 'bg-line-soft', 'bg-line-soft text-[#4A4639]', '#8A8578'],
        'muted' => ['bg-[#CFC8B8]', 'text-[#6C6658]', 'bg-paper', 'bg-paper text-[#6C6658] ring-1 ring-inset ring-line', '#CFC8B8'],
    ];

    public static function bg(?string $key): string
    {
        return self::column($key, 0);
    }

    public static function text(?string $key): string
    {
        return self::column($key, 1);
    }

    public static function soft(?string $key): string
    {
        return self::column($key, 2);
    }

    /** Fond doux + texte foncé : badges et pastilles de statut. */
    public static function pill(?string $key): string
    {
        return self::column($key, 3);
    }

    /** Couleur brute, pour les graphiques (Chart.js) et les styles en ligne. */
    public static function hex(?string $key): string
    {
        return self::column($key, 4);
    }

    private static function column(?string $key, int $index): string
    {
        return (self::MAP[$key] ?? self::MAP['grey'])[$index];
    }
}
