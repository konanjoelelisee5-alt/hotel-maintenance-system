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
        'red' => ['bg-red', 'text-red', 'bg-danger-bg', 'bg-danger-bg text-danger-ink', '#B3261E'],
        'amber' => ['bg-amber', 'text-amber', 'bg-warn-bg', 'bg-warn-bg text-warn-ink', '#B4740F'],
        'gold' => ['bg-gold', 'text-gold', 'bg-warn-bg', 'bg-gold-100 text-gold-700', '#B58435'],
        'green' => ['bg-green', 'text-green', 'bg-ok-bg', 'bg-ok-bg text-[#155C40]', '#1E7A55'],
        'blue' => ['bg-blue', 'text-blue', 'bg-info-bg', 'bg-info-bg text-blue', '#26496B'],
        'navy' => ['bg-navy', 'text-navy', 'bg-info-bg', 'bg-info-bg text-navy', '#0E2136'],
        'grey' => ['bg-ink-grey', 'text-ink-muted', 'bg-line-soft', 'bg-line-soft text-ink-body', '#8A8578'],
        'muted' => ['bg-[#CFC8B8]', 'text-ink-muted', 'bg-paper', 'bg-paper text-ink-muted ring-1 ring-inset ring-line', '#CFC8B8'],
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
