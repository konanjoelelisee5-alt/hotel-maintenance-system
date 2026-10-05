<?php

namespace App\Support;

use App\Enums\IssueCategory;

/**
 * Liste des points vérifiés à chaque inspection de chambre, par zone. Chaque point
 * porte la catégorie de panne de l'OT créé s'il n'est pas conforme. On ne vérifie
 * ici que ce que la maintenance répare (pas la propreté ni le linge : c'est Opera).
 * Les points sont recopiés dans chaque inspection : modifier cette liste ne change
 * pas les inspections passées.
 */
class RoomInspectionChecklist
{
    /** Clé => [zone, libellé, catégorie]. */
    public const POINTS = [
        'porte_serrure' => ['Entrée', 'Serrure et lecteur de carte', IssueCategory::Porte],
        'porte_fermeture' => ['Entrée', 'La porte ferme et se verrouille', IssueCategory::Porte],
        'porte_judas' => ['Entrée', 'Judas, chaîne de sécurité', IssueCategory::Porte],

        'chambre_eclairage' => ['Chambre', 'Éclairage et interrupteurs', IssueCategory::Electricite],
        'chambre_prises' => ['Chambre', 'Prises électriques', IssueCategory::Electricite],
        'chambre_clim' => ['Chambre', 'Climatisation (froid, bruit, télécommande)', IssueCategory::Clim],
        'chambre_tv' => ['Chambre', 'Télévision et télécommande', IssueCategory::Tv],
        'chambre_telephone' => ['Chambre', 'Téléphone', IssueCategory::Tv],
        'chambre_lit' => ['Chambre', 'Lit et tête de lit', IssueCategory::Mobilier],
        'chambre_meubles' => ['Chambre', 'Meubles, placard, tiroirs', IssueCategory::Mobilier],
        'chambre_rideaux' => ['Chambre', 'Rideaux et tringles', IssueCategory::Mobilier],
        'chambre_fenetres' => ['Chambre', 'Fenêtres et baies vitrées', IssueCategory::Autre],
        'chambre_coffre' => ['Chambre', 'Coffre-fort', IssueCategory::Autre],
        'chambre_frigo' => ['Chambre', 'Réfrigérateur ou minibar (il refroidit)', IssueCategory::Electricite],

        'sdb_lavabo' => ['Salle de bain', 'Robinet du lavabo', IssueCategory::Eau],
        'sdb_douche' => ['Salle de bain', 'Douche ou baignoire', IssueCategory::Eau],
        'sdb_eau_chaude' => ['Salle de bain', 'Eau chaude', IssueCategory::Eau],
        'sdb_wc' => ['Salle de bain', 'WC et chasse d\'eau', IssueCategory::Eau],
        'sdb_evacuations' => ['Salle de bain', 'Évacuations (pas de bouchon, pas d\'odeur)', IssueCategory::Eau],
        'sdb_eclairage' => ['Salle de bain', 'Éclairage et extracteur d\'air', IssueCategory::Electricite],
        'sdb_seche_cheveux' => ['Salle de bain', 'Sèche-cheveux', IssueCategory::Electricite],
        'sdb_joints' => ['Salle de bain', 'Joints, carrelage, miroir', IssueCategory::Autre],
    ];

    /** Une chambre non inspectée depuis plus longtemps est signalée « à inspecter ». */
    public const DUE_AFTER_DAYS = 30;

    /** @return array<int, array{point_key: string, zone: string, label: string, category: string}> */
    public static function itemsForNewInspection(): array
    {
        return collect(self::POINTS)->map(fn (array $p, string $key) => [
            'point_key' => $key, 'zone' => $p[0], 'label' => $p[1], 'category' => $p[2]->value,
        ])->values()->all();
    }
}
