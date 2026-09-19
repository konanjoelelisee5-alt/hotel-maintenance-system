# Démo en ligne gratuite (Render + Aiven)

Met l'application en ligne en permanence, sans que ton PC soit allumé, pour que
quelqu'un puisse suivre l'avancement. **C'est une démo, pas une production** :
pour la mise en production réelle, suivre `DEPLOYMENT.md`.

- **Render** (offre gratuite) : fait tourner l'application.
- **Aiven** (offre MySQL gratuite) : héberge la base de données.
- La base est remplie automatiquement avec des **données de démo** (comptes
  fictifs, chambres, ordres de travail...). Aucune donnée réelle n'est envoyée.

## Limites à connaître

| Limite | Conséquence |
|---|---|
| Render met le site en veille après 15 min sans visite | La première visite suivante prend 30 à 60 s, puis tout est rapide |
| Disque temporaire sur Render | Photos et signatures ajoutées sur la démo disparaissent à chaque redémarrage |
| Pas de cron | Alertes SLA et OT préventifs automatiques ne tournent pas (sans effet sur l'affichage) |
| Files d'attente en mode synchrone | Pas de worker gratuit : les notifications sont traitées immédiatement |
| Aiven peut éteindre une base inactive | Erreur sur le site tant qu'elle n'est pas rallumée (voir Dépannage) |
| E-mails désactivés (`MAIL_MAILER=log`) | Aucun e-mail réel n'est envoyé |

## Étape 1 — Envoyer le code sur GitHub (dépôt privé)

1. Créer un compte sur <https://github.com>.
2. Créer un dépôt **privé** nommé `hotel-maintenance`, **vide** (ne cocher ni README, ni .gitignore).
3. Dans le dossier du projet (PowerShell), en remplaçant `TON-PSEUDO` :

```powershell
git remote add origin https://github.com/TON-PSEUDO/hotel-maintenance.git
git push -u origin refonte-design
```

Une fenêtre du navigateur s'ouvre pour t'authentifier. L'envoi pèse environ 190 Mo
et peut prendre quelques minutes.

## Étape 2 — Créer la base de données sur Aiven

1. Créer un compte sur <https://aiven.io> (sans carte bancaire).
2. Créer un service **MySQL**, plan **Free**, région en **Europe** (proche de Render, qui est configuré sur Francfort).
3. Une fois le service démarré, relever dans sa page de connexion :
   **Host**, **Port**, **User**, **Password**, **Database name** (souvent `defaultdb`).
4. Télécharger le **CA certificate** (fichier `ca.pem`) et l'ouvrir avec le Bloc-notes :
   son contenu complet (de `-----BEGIN CERTIFICATE-----` à `-----END CERTIFICATE-----`) sera collé à l'étape 3.

## Étape 3 — Créer le service sur Render

1. Créer un compte sur <https://render.com> en se connectant avec GitHub.
2. **New +** → **Blueprint** → choisir le dépôt `hotel-maintenance` → **branche `refonte-design`**.
3. Render lit `render.yaml` et demande les valeurs suivantes :

| Variable | Valeur |
|---|---|
| `APP_KEY` | la clé générée pour toi (commence par `base64:`), fournie séparément |
| `DB_HOST` | Host d'Aiven |
| `DB_PORT` | Port d'Aiven |
| `DB_DATABASE` | Database name d'Aiven (souvent `defaultdb`) |
| `DB_USERNAME` | User d'Aiven (souvent `avnadmin`) |
| `DB_PASSWORD` | Password d'Aiven |
| `DB_SSL_CA_PEM` | contenu complet de `ca.pem` |

4. Valider (**Apply**). La première construction prend 5 à 10 minutes ; le premier
   démarrage crée les tables et charge les données de démo (1 à 3 minutes de plus).
5. L'adresse du site apparaît en haut de la page du service (`https://....onrender.com`).

## Étape 4 — Se connecter

Comptes de démo (mot de passe **`password`** pour tous) :

| Rôle | E-mail |
|---|---|
| Administrateur | `admin@hotel-test.com` |
| Manager | `manager@hotel-test.com` |

**Change le mot de passe du compte que tu donnes** dès la première connexion :
`password` est connu de tous.

## Mettre à jour la démo

À chaque nouvelle version, depuis le dossier du projet :

```powershell
git push
```

Render reconstruit et redéploie automatiquement (`autoDeploy`).

## Dépannage

- **Le site affiche une erreur 500** : Render → le service → **Logs**. La ligne
  « migration impossible (base de données injoignable ?) » signale un souci Aiven.
- **Base éteinte par Aiven** : console Aiven → le service MySQL → **Power on**, puis
  Render → **Manual Deploy** → **Restart**.
- **Données de démo absentes ou à moitié chargées** : vider la base
  (Aiven → base → supprimer les tables, ou recréer le service) puis relancer le
  déploiement sur Render. Le chargement ne se fait que sur une base **entièrement vide**.
