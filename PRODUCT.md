# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Les employés de l'**Hôtel Président** (propriété de SONAPIE), et eux seuls. Chacun se connecte avec son propre compte et voit l'application de son rôle :

- **Agent housekeeping** : signale une panne depuis la chambre ou l'espace commun, sur son propre téléphone, entre deux ménages ; suit ce qu'il a signalé et confirme la réparation.
- **Gouvernante** (responsable housekeeping) : suit les signalements de son équipe, inspecte les chambres, consulte le plan des étages et le bilan du mois.
- **Réceptionniste** : au comptoir, sur un ordinateur partagé (deux réceptions, mêmes chambres), répond au client sur l'état de sa chambre, signale une panne ou une réclamation, demande le blocage d'une chambre.
- **Responsable réception** : idem, plus les demandes de toute l'équipe et le bilan du mois.
- **Technicien** : reçoit ses ordres de travail, intervient, saisit son temps et ses pièces, clôture.
- **Manager** : répartit le travail le jour (dispatch), gère le stock, les achats et les fournisseurs, valide la qualité.
- **Administrateurs (2)** : le chef de maintenance (reçoit les alertes, astreinte de nuit) et le responsable informatique (paramétrage, sans les alertes de maintenance). Ils se contrôlent mutuellement.

## Product Purpose

Une GMAO (gestion de la maintenance) propre à l'Hôtel Président : chaque panne signalée devient un ordre de travail suivi de bout en bout, de la chambre jusqu'à la réparation confirmée par le service qui l'a demandée.

La réussite se mesure à trois choses :

1. **Les pannes sont réparées plus vite** : délais (SLA) respectés, rien n'est oublié.
2. **Moins de plaintes clients** : la réception sait à tout moment quoi répondre au client, et le client concerné est prévenu de la réparation.
3. **Tout est tracé** : qui a signalé, qui a réparé, quand, avec quelles pièces et à quel coût, pour la direction et pour SONAPIE.

## Positioning

Un outil interne, fait pour un seul hôtel et son organisation réelle (rôles, astreinte jour/nuit, deux réceptions, chambres et espaces communs). Il n'est pas prévu de le proposer à d'autres établissements : les choix suivent les usages de l'Hôtel Président, pas un marché.

## Operating Context

- **Housekeeping** : téléphone personnel de chaque agent, dans les chambres et les couloirs, souvent d'une main ; signalement vocal, photo, numéro de chambre ou QR code ; envoi différé quand le réseau manque.
- **Réception** : ordinateur partagé au comptoir, client souvent en face ; déconnexion automatique après 15 minutes d'inactivité ; « Changer de réceptionniste » pour passer la main.
- **Maintenance** : techniciens sur téléphone, manager et administrateurs sur ordinateur ; astreinte le jour (manager, 07 h–19 h provisoire) et la nuit (chef de maintenance), alertes par téléphone.
- Trois tailles d'écran à servir partout : téléphone (< 700 px), tablette (700–1199 px), ordinateur (≥ 1200 px).

## Capabilities and Constraints

- Laravel 13 / PHP 8.3, Blade, Tailwind 3, Alpine.js, Vite. Application web responsive, pas d'application native.
- Fonctions en place : ordres de travail (création, affectation, statuts, SLA, escalade), signalement rapide HK (vocal, photo, QR), suivi et confirmation par le demandeur, chambres bloquées, inspections de chambres, plan des étages, bilans mensuels HK et réception, planning, maintenance préventive, pièces, bons de commande, fournisseurs, contrôle qualité, rapports et exports (CSV, PDF), journal d'activité, astreinte, notifications.
- **Conditions de terrain à toujours respecter** :
  - téléphones Android modestes, parfois anciens : pages légères, pas d'effets coûteux ;
  - wifi faible par endroits (étages, sous-sols, locaux techniques) : l'action ne doit pas se perdre, l'état du réseau doit se voir ;
  - certains employés lisent peu ou mal : icônes explicites, vocal, peu de texte, mots simples ;
  - français uniquement.
- On ne supprime jamais un lieu ni un équipement : on le met hors service. Départ d'un employé : ses ordres sont réaffectés.
- **Décisions ouvertes** : fournisseur d'alertes SMS / WhatsApp / appel (aujourd'hui seulement écrit dans le journal), notifications sur le téléphone quand l'application est fermée, page d'impression des étiquettes QR, horaires d'astreinte définitifs. Voir `MISE-EN-SERVICE.md`.

## Brand Commitments

- Nom : **Hôtel Président** ; propriétaire : **SONAPIE** (Société Nationale de Gestion du Patrimoine Immobilier de l'État).
- Logos dans `public/images/` : `logo-hotel-president.jpg`, `logo-hotel-president-icon.jpg` (monogramme HP, petite miniature 130 × 130, un peu floue en grand), `logo-sonapie.jpg`.
- Navigation : tous les rôles affichent le monogramme HP et « Hôtel Président » en haut de leur menu (décision du 7 octobre 2026).
- Un seul habillage pour toute l'application : une « feuille » claire et aérée (fond gris perle, grande feuille blanche, sidebar blanche, accent bleu ciel), reprise d'une maquette choisie par l'utilisateur. Chaque rôle y garde sa logique : ses menus, ses indicateurs, sa file de travail et ses panneaux.
- Voix : français simple et direct, vouvoiement, phrases courtes ; pas d'emoji dans l'interface.

## Evidence on Hand

- Logos réels ci-dessus. Données de démonstration (seeders) uniquement : aucun chiffre réel de pannes, de délais ou de coûts n'est encore disponible, il ne faut pas en inventer.
- `MISE-EN-SERVICE.md` : liste tenue à jour des actions avant la mise en service.

## Product Principles

1. **Rien ne se perd** : une panne signalée est suivie jusqu'à la réparation confirmée, même sans réseau au moment du signalement.
2. **Le client d'abord** : quand une panne touche une chambre occupée, la réception le sait et sait quoi dire.
3. **Un geste, sans lire** : les actions du terrain se font d'une main, avec des icônes et la voix, sur un téléphone modeste.
4. **Chacun voit son travail** : chaque rôle arrive sur ce qui le concerne maintenant ; le reste reste à un clic.
5. **Tout laisse une trace** : chaque action importante est datée et attribuée, pour rendre des comptes à la direction et à SONAPIE.

## Accessibility & Inclusion

- Employés à l'aise inégalement avec la lecture : sens porté par l'icône et la couleur en plus du texte, jamais par le texte seul ; signalement vocal disponible.
- Usage d'une main sur téléphone : actions principales dans la zone du pouce, cibles tactiles d'au moins 44 px.
- Contrastes lisibles en plein jour dans les couloirs et au comptoir ; mouvements réduits respectés (`prefers-reduced-motion`).
