# Guide de mise en production

Checklist à suivre avant et pendant le passage à l'hébergement. Compatible VPS
(accès SSH complet) et hébergement mutualisé avec accès SSH.

## 1. Prérequis serveur

- PHP **8.3+** avec extensions : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (ou `imagick` pour les PDF/QC)
- MySQL 8+ (ou MariaDB 10.6+)
- Composer 2
- Node.js 18+ (uniquement pour builder les assets — pas nécessaire à l'exécution)
- Accès cron (même limité à 5 min sur un mutualisé, ça suffit ici)
- Certificat HTTPS (Let's Encrypt, souvent automatique chez l'hébergeur)

## 2. Base de données

1. Créer une base MySQL dédiée + un utilisateur **avec mot de passe** (jamais `root`
   sans mot de passe comme en local).
2. Ne jamais exécuter `db:seed` en production — il crée de fausses données de démo.
3. Exporter la structure/données réelles depuis le dev si besoin de migrer des
   données existantes, sinon partir d'une base vide et laisser les migrations
   la construire.

## 3. Déploiement du code

```bash
git clone <url-du-repo> hotel-maintenance
cd hotel-maintenance

cp .env.example .env
# éditer .env : voir section 4 ci-dessous

composer install --no-dev --optimize-autoloader
npm install && npm run build

php artisan key:generate
php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pour les mises à jour suivantes :

```bash
git pull
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart   # si des jobs sont un jour mis en file
```

## 4. Variables d'environnement critiques (`.env`)

| Variable | Valeur en prod | Pourquoi |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | **Critique** — sinon les erreurs exposent le code source et les requêtes SQL à n'importe quel visiteur |
| `APP_NAME` | Nom réel de l'hôtel | Actuellement encore "Laravel" |
| `APP_URL` | `https://ton-domaine.com` | |
| `APP_KEY` | générée sur le serveur (`artisan key:generate`) | Ne jamais réutiliser la clé de dev |
| `DB_*` | identifiants dédiés à la prod | Jamais `root` sans mot de passe |
| `MAIL_MAILER` | `smtp` (ou `ses`/`postmark`/`mailgun`) | Actuellement `log` = **aucun email n'est réellement envoyé** (reset mot de passe, alertes SLA, stock bas...) |
| `SESSION_SECURE_COOKIE` | `true` | Nécessite HTTPS actif |
| `LOG_LEVEL` | `error` (ou `warning`) | `debug` est trop verbeux en prod |

## 5. Cron — indispensable pour l'automatisation

Sans cette tâche, **rien de l'automatisation ne se déclenche** (ni la vérification
SLA/escalade toutes les 15 min, ni la génération des OT préventifs chaque jour à 5h) :

```
* * * * * cd /chemin/vers/hotel-maintenance && php artisan schedule:run >> /dev/null 2>&1
```

Sur un mutualisé avec cron limité à 5 min, c'est sans incidence : Laravel vérifie
en interne quelles tâches sont réellement dues.

Vérifier que ça tourne : `php artisan schedule:list` affiche les tâches planifiées ;
les commandes `work-orders:check-sla` et `maintenance:generate-preventive-work-orders`
doivent y apparaître.

## 6. Permissions fichiers

Le serveur web doit pouvoir écrire dans :

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache   # adapter l'utilisateur selon l'hébergeur
```

## 7. Sauvegardes

Aucune sauvegarde automatique n'existe actuellement (seulement un dump SQL fait
à la main en local, jamais commité). Au minimum, avant de considérer le projet
"en production" :

- Dump MySQL quotidien automatisé (cron `mysqldump` + rotation, ou fonctionnalité
  de backup de l'hébergeur si disponible)
- Copie régulière de `storage/app/public` (photos jointes aux OT, signatures
  d'intervention) — ces fichiers ne sont pas dans la base de données

## 8. Vérifications post-déploiement

- [ ] Se connecter avec chaque rôle (admin/manager/technicien/housekeeping/réception)
- [ ] Créer un OT, l'assigner, le faire passer par tout le cycle de statuts
- [ ] Uploader une pièce jointe et vérifier qu'elle s'affiche (teste `storage:link`)
- [ ] Déclencher un envoi d'email réel (mot de passe oublié) et vérifier la réception
- [ ] Vérifier `php artisan schedule:list` et repasser dans 24h pour confirmer
      qu'un plan de maintenance préventive a bien généré son OT
- [ ] Vérifier que `https://ton-domaine.com` redirige bien `http://` vers `https://`
- [ ] Confirmer qu'une page d'erreur ne montre plus de stack trace (`APP_DEBUG=false`)

## 9. Non bloquant, à prévoir après le lancement

- 2FA pour les comptes admin
- Monitoring d'erreurs (Sentry, Flare, ou simple surveillance de `storage/logs/laravel.log`)
- API mobile pour les techniciens sur le terrain
- Intégration PMS (statut chambre)
