# Mise en service — liste des actions à mener

Liste tenue à jour au fil du développement. **Quand une action est faite, elle est retirée.**
Ce qui reste ici est donc, à tout moment, ce qu'il reste à faire.

Dernière mise à jour : 5 octobre 2026.

---

## 1. En attente d'un développement

Ces points bloquent ou gênent la mise en service ; ils seront développés, puis retirés d'ici.

- [ ] **Brancher un vrai fournisseur de SMS** pour les alertes d'astreinte. Aujourd'hui, les SMS sont seulement écrits dans le journal (`PHONE_ALERTS_DRIVER=log`) : personne n'est réellement appelé la nuit. À choisir : opérateur local (Orange, MTN, Moov) ou service international (Twilio…).
- [ ] **Page d'impression des étiquettes QR** (une étiquette par chambre), pour pouvoir les coller sur les portes.
- [ ] **Notifications sur le téléphone** (application installable) : prévenir l'agent même quand l'application est fermée. *Souhaitable, pas bloquant.*

## 2. Préparer le serveur

- [ ] Choisir où tourne l'application : serveur dans l'hôtel, ou serveur sur Internet.
- [ ] Obtenir un **nom de domaine** (ou un sous-domaine du site de l'hôtel, ex. `gmao.<domaine>`).
- [ ] Installer un **certificat https** (Let's Encrypt, gratuit). Sans https, le micro, la photo et le scan QR ne marchent pas sur les téléphones.
- [ ] Installer PHP 8.3, MySQL, Composer et Node.js sur le serveur.
- [ ] Copier le code, puis :
  - `composer install --no-dev --optimize-autoloader`
  - `npm ci` puis `npm run build`
- [ ] Créer le fichier `.env` de production :
  - `APP_ENV=production`, `APP_DEBUG=false`
  - `APP_URL=https://…` (l'adresse définitive)
  - `APP_LOCALE=fr`
  - `APP_KEY` : `php artisan key:generate` (une seule fois)
  - connexion à la base MySQL
  - **e-mails** : renseigner un vrai serveur d'envoi (`MAIL_MAILER=smtp`, `MAIL_HOST`…). Aujourd'hui `MAIL_MAILER=log` : « mot de passe oublié » n'envoie rien.
  - **SMS d'astreinte** : `PHONE_ALERTS_DRIVER` et les accès du fournisseur (voir partie 1).
  - **ne pas** mettre `SEED_DEMO_DATA` (les données de démo sont refusées en production).
- [ ] `php artisan migrate --force` (crée toutes les tables, dont celles des inspections).
- [ ] `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`.
- [ ] **Tâches automatiques** : planifier `php artisan schedule:run` toutes les minutes (cron sous Linux, Planificateur de tâches sous Windows). Sans elles : pas d'escalade des retards, pas d'OT préventifs à 5 h, pas d'alerte « client qui revient dans une chambre en panne ».
- [ ] **File d'attente** (`QUEUE_CONNECTION=database`) : lancer un `php artisan queue:work` permanent, ou passer `QUEUE_CONNECTION=sync`.
- [ ] **Sauvegardes** : sauvegarde automatique quotidienne de la base MySQL et du dossier `storage/app` (photos, messages vocaux). Tester une restauration une fois.

## 3. Préparer les données

- [ ] Saisir toutes les **chambres** (numéro, étage) et les **espaces communs** dans « Lieux ». Mettre « hors service » celles qui le sont.
- [ ] Créer les **comptes** de chaque personne, avec le bon rôle : agents HK, gouvernante, réception, techniciens, manager, administrateurs.
- [ ] Cocher **« Responsable de service »** pour la gouvernante et pour le responsable de la réception.
- [ ] Renseigner les **téléphones** des personnes d'astreinte, et régler les **horaires d'astreinte** (Paramètres → Astreinte).
- [ ] Vérifier les **délais garantis (SLA)** et les **priorités** (Paramètres).
- [ ] Remettre à chacun son mot de passe provisoire (il devra le changer à la première connexion).
- [ ] Imprimer et **coller les QR codes** sur les portes des chambres (après le développement de la page d'impression).

## 4. Tester avec le personnel (recette)

Sur de vrais téléphones : au moins un **Android** et un **iPhone**, en https.

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
  - [ ] Plan des étages ; faire une inspection complète avec photo ; lire le bilan du mois.
  - [ ] Demander le blocage d'une chambre ; la remettre en vente.
- [ ] **Technicien** : recevoir un OT, voir les précisions de l'agent, saisir son temps et son rapport, déclarer « réparé ».
- [ ] **Réception** : recevoir l'alerte « client dans une chambre en panne », décider d'un blocage.
- [ ] **Manager / admin** : affecter, planifier, contrôle qualité, recevoir l'alerte d'astreinte par SMS (nuit et jour).
- [ ] Noter les remarques de chacun et les transmettre pour correction.

## 5. Mise en production

- [ ] Former chaque groupe (15 minutes suffisent par rôle) ; laisser une fiche « comment signaler une panne » à l'office des étages.
- [ ] Choisir une date de démarrage et prévenir les équipes.
- [ ] Première semaine : consulter chaque jour le journal d'erreurs (`storage/logs/laravel.log`) et les remarques des équipes.
- [ ] Vérifier au bout d'une semaine que les sauvegardes tournent bien.
