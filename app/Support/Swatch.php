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
        'blue' => ['bg-blue', 'text-blue', 'bg-info-bg', 'bg-info-bg text-[rgb(var(--rc-sky-ink))]', '#26496B'],
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

    /**
     * Texte lisible pour une couleur libre (priorité choisie par l'admin) posée sur son propre
     * fond teinté (la couleur à 0x18/255 sur blanc) : foncée pas à pas jusqu'à 4,5:1 (WCAG AA).
     */
    public static function readableOnTint(?string $hex): string
    {
        $hex = ltrim((string) $hex, '#');
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return '#666A74';
        }
        $rgb = array_map('hexdec', str_split($hex, 2));
        $lum = function (array $c): float {
            $c = array_map(fn ($v) => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4, $c);

            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };
        $bg = $lum(array_map(fn ($v) => $v * 0x18 / 255 + 255 * (1 - 0x18 / 255), $rgb));
        $text = $rgb;
        for ($i = 0; $i < 20 && ($bg + 0.05) / ($lum($text) + 0.05) < 4.5; $i++) {
            $text = array_map(fn ($v) => $v * 0.88, $text);
        }

        return vsprintf('#%02X%02X%02X', array_map(fn ($v) => (int) round($v), $text));
    }

    private static function column(?string $key, int $index): string
    {
        return (self::MAP[$key] ?? self::MAP['grey'])[$index];
    }
}
