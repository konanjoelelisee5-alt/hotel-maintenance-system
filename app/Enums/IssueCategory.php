<?php

namespace App\Enums;

/**
 * Catégories du signalement rapide : un pictogramme par famille de panne, pour
 * qu'un agent qui lit difficilement puisse décrire le problème sans écrire.
 * Elles nourrissent le titre de l'OT ; le type et la priorité réels restent
 * ceux du référentiel (le manager les ajuste au dispatch).
 */
enum IssueCategory: string
{
    case Eau = 'eau';
    case Electricite = 'electricite';
    case Clim = 'clim';
    case Tv = 'tv';
    case Mobilier = 'mobilier';
    case Porte = 'porte';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Eau => 'Eau / fuite',
            self::Electricite => 'Électricité / lumière',
            self::Clim => 'Climatisation',
            self::Tv => 'TV / téléphone',
            self::Mobilier => 'Meuble / lit',
            self::Porte => 'Porte / serrure',
            self::Autre => 'Autre',
        };
    }
}
