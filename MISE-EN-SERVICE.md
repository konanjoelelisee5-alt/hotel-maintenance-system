# Mise en service — liste des actions à mener

Liste tenue à jour au fil du développement. **Quand une action est faite, elle est retirée.**
Ce qui reste ici est donc, à tout moment, ce qu'il reste à faire.

Dernière mise à jour : 7 octobre 2026 (accès ouvert de démonstration).

---

## 1. En attente d'un développement

Ces points bloquent ou gênent la mise en service ; ils seront développés, puis retirés d'ici.

- [ ] **Brancher un vrai fournisseur de SMS** pour les alertes d'astreinte. Aujourd'hui, les SMS sont seulement écrits dans le journal (`PHONE_ALERTS_DRIVER=log`) : personne n'est réellement appelé la nuit. À choisir : opérateur local (Orange, MTN, Moov) ou service international (Twilio…).
- [ ] **Page d'impression des étiquettes QR** (une étiquette par chambre), pour pouvoir les coller sur les portes.

## 2. Préparer le serveur

- [ ] Choisir où tourne l'application : serveur dans l'hôtel, ou serveur sur Internet.
- [ ] Obtenir un **nom de domaine** (ou un sous-domaine du site de l'hôtel, ex. `gmao.<domaine>`).
- [ ] Installer un **certificat https** (Let's Encrypt, gratuit). Sans https, le micro, la photo et le scan QR ne marchent pas sur les téléphones.
- [ ] Installer PHP 8.4, MySQL, Composer et Node.js sur le serveur.
- [ ] **Sécurité du serveur** (audit du 8 octobre 2026) :
  - la racine web du serveur doit être le dossier `public/` du projet, jamais le projet entier (sinon `.env`, les journaux et la base sont téléchargeables) ;
  - MySQL : un mot de passe solide pour `root` et un compte dédié à l'application, et `bind-address=127.0.0.1` si la base est sur le même serveur ;
  - pare-feu : n'ouvrir que les ports 80 et 443 ;
  - `TRUSTED_PROXIES` : vide si le serveur est exposé directement, `*` seulement derrière un proxy (hébergeur, répartiteur de charge) ;
  - `APP_DEBUG=false` ; ne jamais copier le `.env` de développement : générer une nouvelle `APP_KEY`.
- [ ] Copier le code, puis :
  - `composer install --no-dev --optimize-autoloader`
  - `npm ci` puis `npm run build`
- [ ] Créer le fichier `.env` de production :
  - `APP_ENV=production`, `APP_DEBUG=false`
  - `APP_OPEN_ACCESS=false` (ou ligne absente) : l'accès ouvert de démonstration (bouton « Voir en tant que ») lève les contrôles de rôle. En production, il ne s'active qu'avec `SEED_DEMO_DATA=true` (site de démo Render) : sans ce réglage, il est ignoré, mais il ne doit pas rester actif sur un serveur de test accessible au personnel.
  - `APP_URL=https://…` (l'adresse définitive)
  - `APP_LOCALE=fr`
  - `APP_KEY` : `php artisan key:generate` (une seule fois)
  - connexion à la base MySQL
  - **e-mails** : renseigner le serveur d'envoi (`MAIL_MAILER=smtp`, `MAIL_HOST`…, modèle Gmail dans `.env.example`). Ils servent pour « mot de passe oublié », les urgences (astreinte, retards) et les actions sensibles des admins. Chaque compte doit avoir une **vraie adresse e-mail** (chef de maintenance, managers, admins). Avec `MAIL_MAILER=log`, rien n'est envoyé.
  - **SMS d'astreinte** : `PHONE_ALERTS_DRIVER` et les accès du fournisseur (voir partie 1).
  - **notifications push** : `VAPID_SUBJECT` (l'adresse e-mail de l'hôtel, en `mailto:`), puis `php artisan webpush:vapid` **une seule fois** : changer les clés plus tard désabonne tous les appareils. Le push exige le https. Serveur Windows : PHP doit avoir `OPENSSL_CONF` (chemin d'un `openssl.cnf`) et `curl.cainfo` / `openssl.cafile` dans `php.ini`. C'est déjà réglé sur le PC de développement.
  - **ne pas** mettre `SEED_DEMO_DATA` (les données de démo sont refusées en production).
- [ ] `php artisan migrate --force` (crée toutes les tables, dont celles des inspections).
- [ ] `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
- [ ] **Tâches automatiques** : planifier `php artisan schedule:run` toutes les minutes (cron sous Linux, Planificateur de tâches sous Windows). Sans elles : pas d'escalade des retards, pas d'OT préventifs à 5 h, pas d'alerte « client qui revient dans une chambre en panne ». Vérifier ensuite le panneau « Tâches automatiques » du tableau de bord admin : les trois lignes doivent être vertes.
  - Sous Windows, modèle déjà en place sur le PC de développement : tâche « Hotel President - taches automatiques » (chaque minute) qui lance `%LOCALAPPDATA%\HotelPresident\planificateur.vbs` (sans fenêtre, journal dans `storage/logs/planificateur.log`). Sur un serveur, la régler sur « exécuter même si l'utilisateur n'est pas connecté ».
- [ ] **File d'attente** (`QUEUE_CONNECTION=database`) : les e-mails et les notifications push y attendent leur envoi. Les tâches automatiques ci-dessus la vident chaque minute : rien de plus à lancer. Un `php artisan queue:work` permanent (supervisor, service Windows) les envoie à la seconde au lieu de la minute. Vérifier qu'un e-mail d'urgence arrive en moins de 2 minutes ; sinon, regarder `php artisan queue:failed`.
- [ ] **Sauvegardes** : sauvegarde automatique quotidienne de la base MySQL et du dossier `storage/app` (photos, messages vocaux). Tester une restauration une fois.

## 3. Préparer les données

- [ ] Saisir toutes les **chambres** (numéro, étage) et les **espaces communs** dans « Lieux ». Mettre « hors service » celles qui le sont.
- [ ] Créer les **comptes** de chaque personne, avec le bon rôle : agents HK, gouvernante, réception, techniciens, manager, administrateurs.
- [ ] Cocher **« Responsable de service »** pour la gouvernante et pour le responsable de la réception (c'est ce qui leur donne leur bilan du mois).
- [ ] Créer **un compte par réceptionniste** (pas un compte commun « réception ») : les postes du comptoir sont partagés, chacune se connecte avec le sien.
- [ ] Renseigner les **téléphones** des personnes d'astreinte, et régler les **horaires d'astreinte** (Paramètres → Astreinte).
- [ ] Vérifier les **délais garantis (SLA)** et les **priorités** (Paramètres).
- [ ] Garder actif le type d'OT **« Demande client »** (Paramètres → Types d'OT) : les réclamations de clients transmises par la réception l'utilisent.
- [ ] Remettre à chacun son mot de passe provisoire (il devra le changer à la première connexion).
- [ ] Imprimer et **coller les QR codes** sur les portes des chambres (après le développement de la page d'impression).

## 4. Tester avec le personnel (recette)

Sur de vrais téléphones : au moins un **Android** et un **iPhone**, en https.

- [ ] **Application installée (PWA)**
  - [ ] Android (Chrome) : l'invitation « Installer l'application » apparaît ; installer, puis ouvrir depuis l'icône « Maintenance » : plein écran, on arrive sur son tableau de bord.
  - [ ] iPhone (Safari) : suivre l'explication « Partager → Sur l'écran d'accueil » ; ouvrir depuis l'icône.
  - [ ] Couper le réseau puis ouvrir une page : la page « Pas de connexion » s'affiche, et la page revient seule au retour du réseau.
  - [ ] Micro, photo et scan QR fonctionnent aussi dans l'application installée.

- [ ] **Agent HK**
  - [ ] Signaler une panne : chambre au pavé numérique, chambre au scan QR, espace commun.
  - [ ] Message vocal (le navigateur demande l'accès au micro : accepter), photo, précisions.
  - [ ] Signaler la même panne deux fois : l'avertissement « Déjà signalé » apparaît.
  - [ ] Couper le wifi et les données, envoyer, remettre le réseau : le signalement part tout seul.
  - [ ] Retirer un signalement fait par erreur (dans les 15 minutes).
  - [ ] Ajouter une précision ; recevoir les notifications « technicien affecté » et « réparé ».
  - [ ] Confirmer une réparation, ou rouvrir si ce n'est pas réglé.
- [ ] **Gouvernante**
  - [ ] Voir les signalements de l'équipe, la charge par agent, les pannes récurrentes.
  - [ ] Être prévenue d'une urgence signalée par un agent.
  - [ ] Plan des étages ; faire une inspection complète avec photo, zone par zone (« Continuer » attend que tous les points de la zone soient notés) ; lire le bilan du mois.
  - [ ] Demander le blocage d'une chambre ; la remettre en vente.
- [ ] **Technicien**
  - [ ] Recevoir un OT, voir les précisions de l'agent, saisir son temps et son rapport, déclarer « réparé ».
  - [ ] Sur la fiche, lire la situation du client (dans la chambre, sorti avec heure de retour, arrivée prévue) après un changement fait par la réception.
  - [ ] Voir « Déjà réparé ici » sur une chambre qui a déjà eu une panne, avec ce qui a été fait.
  - [ ] Dans « À savoir avant de partir » : les pièces à prendre au magasin et les clients à ménager.
  - [ ] Ouvrir un nouvel OT et appuyer sur « J'ai vu, je m'en occupe » : le manager ne voit plus « Pas encore vu » ; pour une urgence, l'astreinte est prévenue.
  - [ ] Signaler une autre panne trouvée sur place depuis le menu « Signaler », puis la retrouver.
  - [ ] Demander une pièce absente du magasin (OT mis en attente) ; le manager la voit sur son accueil, la marque « traitée », le technicien est prévenu.
- [ ] **Réception** (ordinateur du comptoir et téléphone)
  - [ ] Signaler une panne en 4 étapes (lieu et client, problème, précisions, vérifier et envoyer) : « Continuer » reste grisé tant qu'une étape n'est pas remplie, « Modifier » ramène à l'étape à corriger.
  - [ ] Rechercher une chambre signalée par le Housekeeping : la phrase « À dire au client » est juste.
  - [ ] Transmettre une réclamation de client (case cochée) ; à la réparation, recevoir « Prévenez le client », puis confirmer.
  - [ ] Signaler une panne déjà connue : l'avertissement renvoie vers la chambre.
  - [ ] Déclarer la situation du client : relogé, sorti (heure de retour), arrivée prévue, parti ; le technicien affecté est prévenu.
  - [ ] Recevoir l'alerte « client dans une chambre en panne » : le clic ouvre la chambre.
  - [ ] Vérifier l'encadré d'astreinte de jour et de nuit. Sur téléphone, « Appeler » ouvre le composeur avec le bon numéro (crédit de l'appelant) et « WhatsApp » ouvre la conversation (data). Sur ordinateur, « Appeler » affiche le numéro en grand ; « Copier » le copie.
  - [ ] Vérifier que les numéros de WhatsApp sont bien ceux de l'astreinte : un numéro saisi sans indicatif est considéré comme ivoirien (+225).
  - [ ] Décider d'un blocage (accepter, refuser avec motif).
  - [ ] Laisser l'accueil ouvert : une nouvelle alerte apparaît seule en moins d'une minute, avec un bip.
  - [ ] Retirer une demande faite par erreur (15 minutes) ; ajouter une précision quand le client rappelle.
  - [ ] **Poste partagé** : « Changer de réceptionniste » ; laisser le poste 15 minutes sans y toucher → « Toujours là ? », puis déconnexion.
  - [ ] Responsable de réception : lire le bilan du mois (réclamations clients, délais) et l'imprimer.
- [ ] **Manager / admin** : affecter, planifier, contrôle qualité, recevoir l'alerte d'astreinte par SMS (nuit et jour) ; créer un bon de commande en 3 étapes (fournisseur, articles, vérification).
- [ ] **Notifications push** sur un Android, un iPhone (application installée sur l'écran d'accueil) et un ordinateur : page Notifications, puis « Activer ». Déclencher une notification (OT affecté, urgence…), application fermée : elle arrive en moins de 2 minutes, et la toucher ouvre la bonne page. « Couper » l'arrête. Sur le poste de la réception : se déconnecter, puis vérifier que les notifications de l'ancienne personne n'arrivent plus.
- [ ] **Ergonomie sur vrai téléphone** (Android et iPhone) : saisir dans un champ en bas d'écran (précision, motif, numéro de chambre) — le clavier ne doit masquer ni le champ ni le bouton d'envoi ; la flèche « retour » ramène à l'écran précédent ; couper le réseau affiche le bandeau « Pas de connexion Internet ».
- [ ] **Ergonomie sur vraie tablette** (portrait et paysage), pour chaque rôle : le menu (3 traits, en haut à gauche) s'ouvre depuis la gauche et donne accès à tous les écrans ; la colonne de droite de l'accueil (astreinte, activité) passe sous le contenu ; aucun écran ne défile de côté.
- [ ] Noter les remarques de chacun et les transmettre pour correction.

## 5. Mise en production

- [ ] Former chaque groupe (15 minutes suffisent par rôle) ; laisser une fiche « comment signaler une panne » à l'office des étages.
- [ ] Choisir une date de démarrage et prévenir les équipes.
- [ ] Première semaine : consulter chaque jour le journal d'erreurs (`storage/logs/laravel.log`) et les remarques des équipes.
- [ ] Vérifier au bout d'une semaine que les sauvegardes tournent bien.
