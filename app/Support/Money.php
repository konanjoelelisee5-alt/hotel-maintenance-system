<?php

namespace App\Support;

/**
 * Montants en franc CFA (XOF), la monnaie de l'hôtel : pas de centimes, espace
 * comme séparateur de milliers (« 150 000 FCFA »). Espaces insécables pour que
 * le montant ne soit jamais coupé en fin de ligne (écran comme PDF).
 */
class Money
{
    private const NBSP = "\u{00A0}";

    public static function format(int|float|string|null $amount): string
    {
        return self::number($amount).self::NBSP.config('app.currency', 'FCFA');
    }

    /** Le nombre seul (« 150 000 »), pour les colonnes dont l'en-tête porte déjà la monnaie. */
    public static function number(int|float|string|null $amount): string
    {
        return number_format(round((float) $amount), 0, ',', self::NBSP);
    }
}
