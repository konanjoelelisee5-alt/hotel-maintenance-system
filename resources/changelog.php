<?php

/*
|--------------------------------------------------------------------------
| Journal des versions (page « Nouveautés »)
|--------------------------------------------------------------------------
|
| Source unique du numéro de version : c'est la PREMIÈRE entrée (App\Support\AppVersion).
| À chaque modification livrée, ajouter une entrée EN HAUT (ou compléter la version
| en cours si elle n'a pas encore été donnée au personnel).
|
| Numéro « majeur.mineur.correctif » :
|   - correctif (0.9.1) : une petite correction ;
|   - mineur (0.10.0)  : une nouvelle fonction ;
|   - majeur (1.0.0)   : la mise en service, ou une refonte.
| Pendant les tests avec le personnel : suffixe « -beta.N » (0.9.0-beta.2…), puis
| « -rc.N » pour la dernière vérification. Sans suffixe = version définitive (badge
| « Test » retiré).
|
| Chaque changement : type (nouveau | ameliore | corrige), texte écrit pour le
| personnel (pas de jargon), roles = rôles concernés (UserRole), ou null pour tous.
| Une version sans rien pour un rôle ne lui est pas montrée.
|
*/

$all = null;
$pilots = ['admin', 'manager'];
$requesters = ['housekeeping', 'reception'];

return [
    [
        'version' => '0.9.0-beta.1',
        'date' => '2026-10-12',
        'title' => 'Première version de test',
        'changes' => [
            ['type' => 'nouveau', 'roles' => $all, 'text' => "L'application s'installe sur le téléphone ou l'ordinateur : une icône « Maintenance » sur l'écran d'accueil, ouverte en plein écran."],
            ['type' => 'ameliore', 'roles' => $all, 'text' => "Nouvel écran de chargement aux couleurs de l'hôtel, qui s'adapte à chaque écran : texte à gauche sur ordinateur, tablette et téléphone couché ; texte en haut et photo en bas sur téléphone debout. La ligne dorée dit l'étape du chargement."],
            ['type' => 'nouveau', 'roles' => $all, 'text' => "Sans réseau, une page « Pas de connexion » s'affiche et revient seule quand le réseau revient."],
            ['type' => 'nouveau', 'roles' => $all, 'text' => 'Le numéro de version est affiché en bas du menu, et cette page « Nouveautés » dit ce qui change à chaque version.'],
            ['type' => 'nouveau', 'roles' => $all, 'text' => "Quand une nouvelle version est prête, un message propose d'actualiser la page."],
            ['type' => 'nouveau', 'roles' => $all, 'text' => 'Chaque ordre de travail montre la même frise de suivi pour tout le monde : Signalé → Pris en charge → En réparation → Réparé → Contrôlé → Confirmé.'],
            ['type' => 'nouveau', 'roles' => ['technicien'], 'text' => "L'onglet « Intervention » devient un parcours en 4 étapes, comme le signalement : 1. Préparer (déjà réparé ici ?, avez-vous besoin de pièces ?), 2. Sur place (chrono, « La panne est-elle réparée ? »), 3. Pièces utilisées, 4. Rapport et signature. « Continuer » reste grisé tant que l'étape n'est pas complète."],
            ['type' => 'nouveau', 'roles' => ['technicien'], 'text' => "« Non, je suis bloqué » : choisissez pourquoi (pièce manquante, client dans la chambre, accès impossible, prestataire). L'ordre passe en attente avec le motif et le chrono s'arrête tout seul."],
            ['type' => 'nouveau', 'roles' => ['technicien'], 'text' => "Après « Signer et envoyer », l'écran « Réparation envoyée » récapitule l'intervention et montre ce qui reste à vérifier (contrôle qualité, confirmation du service). « Retour à ma journée » pour sortir ; vous êtes prévenu quand la réparation est validée ou qu'une correction est demandée."],
            ['type' => 'ameliore', 'roles' => ['technicien'], 'text' => "Plus de pastilles « En cours / En attente / Résolu » à choisir : l'état suit vos gestes (chrono démarré, blocage, signature). Le rapport reprend les pièces sorties du magasin."],
            ['type' => 'nouveau', 'roles' => ['technicien'], 'text' => "Sur la fiche d'un ordre, la carte « À vous » donne le prochain geste en un seul bouton : J'ai vu, Démarrer le chrono, Rédiger le rapport, Reprendre, Voir la correction."],
            ['type' => 'ameliore', 'roles' => $pilots, 'text' => "Le panneau « Pilotage » est placé au-dessus des onglets de la fiche : la prochaine étape reste visible quel que soit l'onglet ouvert."],
            ['type' => 'ameliore', 'roles' => $requesters, 'text' => 'Sous la frise de suivi, une phrase dit qui doit agir maintenant (affectation, réparation, contrôle, votre confirmation).'],
            ['type' => 'ameliore', 'roles' => ['admin', 'manager', 'technicien'], 'text' => "Sur téléphone, la barre du bas ne garde que vos pages de tous les jours. Le profil (et les paramètres pour l'administrateur) sont dans le menu ⋮ en haut à droite."],
            ['type' => 'ameliore', 'roles' => $requesters, 'text' => "Sur téléphone, plus de menu en haut à gauche : vos pages sont dans la barre du bas et sur l'accueil. Votre profil, la déconnexion et les nouveautés sont derrière le rond à vos initiales, à droite de la cloche."],
            ['type' => 'corrige', 'roles' => $all, 'text' => "Sur téléphone, chaque page a maintenant une sortie en haut à gauche : la flèche ← revient à l'écran d'avant ; la croix (profil, notifications, nouveautés) ramène là où vous étiez avant de l'ouvrir. Les pages de la barre du bas gardent le logo."],
            ['type' => 'ameliore', 'roles' => $pilots, 'text' => "Sur téléphone, dans le menu ⋮, un rond bleu avec une flèche vous emmène d'un geste en bas de la liste, puis vous ramène en haut."],
            ['type' => 'ameliore', 'roles' => ['admin'], 'text' => "Le tableau de bord n'a plus le bouton « onglets » d'activité en haut de l'écran : l'activité de chaque ordre se lit dans sa fiche, onglet « Échanges »."],
            ['type' => 'ameliore', 'roles' => ['reception'], 'text' => "Responsable de réception : sur téléphone, le « Bilan du mois de l'équipe » est sur l'accueil."],
            ['type' => 'ameliore', 'roles' => $all, 'text' => "Carte « Astreinte » : sur téléphone, « Appeler » lance l'appel directement avec votre crédit, et le nouveau bouton WhatsApp appelle par Internet. Sur ordinateur, « Appeler » affiche le numéro en grand avec un bouton « Copier », pour le composer depuis votre téléphone."],
            ['type' => 'nouveau', 'roles' => $pilots, 'text' => "Les urgences arrivent aussi par e-mail : un ordre urgent ou haut signalé pendant votre astreinte, ou une urgence restée sans suite. Le bouton de l'e-mail ouvre directement l'ordre."],
            ['type' => 'nouveau', 'roles' => ['admin'], 'text' => "Quand l'autre administrateur fait une action sensible (compte désactivé, rôle changé, règles de délai…), vous recevez aussi un e-mail : une trace hors de l'application."],
            ['type' => 'nouveau', 'roles' => $all, 'text' => "« Mot de passe oublié » envoie vraiment l'e-mail pour en choisir un nouveau, en français."],
            ['type' => 'nouveau', 'roles' => $all, 'text' => "Notifications sur le téléphone ou l'ordinateur, même application fermée : page Notifications, bouton « Activer » (une fois par appareil). Tout ce qui sonne dans la cloche vous arrive alors comme un SMS ; le toucher ouvre la bonne page. Sur iPhone, installez d'abord l'application sur l'écran d'accueil."],
            ['type' => 'nouveau', 'roles' => ['reception'], 'text' => "Poste partagé : à la déconnexion, l'ordinateur arrête de recevoir vos notifications ; il reçoit celles de la personne qui se connecte ensuite."],
        ],
    ],
];
