#!/bin/sh
# Démarrage du conteneur : configure Apache, prépare Laravel, migre la base,
# charge les données de démo (une seule fois), puis lance Apache.
set -e
cd /var/www/html

# Render fournit le port d'écoute dans $PORT
PORT="${PORT:-10000}"
sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# APP_KEY : tolère les erreurs de copier-coller (espaces, guillemets, préfixe
# "base64:" oublié) et signale clairement une clé inutilisable.
APP_KEY="$(printf '%s' "${APP_KEY:-}" | tr -d "[:space:]\"'")"
if printf '%s' "$APP_KEY" | grep -Eq '^[A-Za-z0-9+/]{43}=$'; then
    APP_KEY="base64:$APP_KEY"
fi
if ! printf '%s' "$APP_KEY" | grep -Eq '^base64:[A-Za-z0-9+/]{43}=$'; then
    # Clé inutilisable (ex. valeur générée par Render dans un autre format) :
    # on en dérive une clé valide, stable tant que la variable ne change pas.
    # Sans valeur du tout, clé aléatoire : les sessions sautent à chaque redémarrage.
    if [ -n "$APP_KEY" ]; then
        echo "ATTENTION : APP_KEY au mauvais format : clé valide dérivée automatiquement."
        APP_KEY="$(php -r 'echo "base64:".base64_encode(hash("sha256", $argv[1], true));' "$APP_KEY")"
    else
        echo "ATTENTION : APP_KEY absente : clé aléatoire générée (sessions perdues à chaque redémarrage)."
        APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    fi
fi
export APP_KEY

# Base MySQL : certificat CA (Aiven) fourni en variable d'environnement, puis
# test de connexion dont le résultat détaillé apparaît dans les logs ("DB-CHECK").
# Secours pour la démo : DB_SSL_INSECURE=true chiffre la connexion sans vérifier
# le certificat du serveur (à n'utiliser qu'avec des données fictives).
if [ "${DB_SSL_INSECURE:-false}" = "true" ]; then
    echo "ATTENTION : DB_SSL_INSECURE=true : connexion chiffrée mais serveur non vérifié (démo uniquement)."
    export MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
    export MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=false
elif [ -n "${DB_SSL_CA_PEM:-}" ]; then
    php /var/www/html/docker/db-ca.php || true
    if [ -s /etc/ssl/db-ca.pem ]; then
        export MYSQL_ATTR_SSL_CA=/etc/ssl/db-ca.pem
    else
        # Certificat fourni mais inutilisable : mieux vaut une connexion chiffrée
        # non vérifiée qu'un site en erreur. À corriger en recopiant ca.pem.
        echo "ATTENTION : certificat CA inutilisable : repli sur une connexion chiffrée SANS vérification du serveur."
        export MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
        export MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=false
    fi
fi
php /var/www/html/docker/db-check.php || true

php artisan storage:link --force > /dev/null 2>&1 || true
php artisan config:cache || echo "ATTENTION : config:cache a échoué"
php artisan view:cache || echo "ATTENTION : view:cache a échoué"

# Une base injoignable ne doit pas empêcher Apache de démarrer : le site
# affichera une erreur au lieu de boucler en redémarrage.
if php artisan migrate --force; then
    if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
        USERS="$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
        if [ "$USERS" = "0" ]; then
            echo "Base vide : chargement des données de démo..."
            php artisan db:seed --force || echo "ATTENTION : le chargement des données de démo a échoué"
        fi
    fi
else
    echo "ATTENTION : migration impossible (base de données injoignable ?)"
fi

# Les commandes artisan ci-dessus tournent en root : rendre le stockage à Apache
chown -R www-data:www-data storage bootstrap/cache

# Planificateur (vérification des SLA toutes les 15 min, OT préventifs à 5 h) :
# sans lui, aucune escalade ne part. Lancé en arrière-plan sous l'utilisateur
# d'Apache (pas de fichiers root dans storage/), et relancé s'il s'arrête.
# Le tableau de bord admin ("Tâches automatiques") montre s'il tourne vraiment.
if [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    echo "Planificateur : démarrage en arrière-plan (schedule:work)."
    su www-data -s /bin/sh -c '
        while true; do
            php artisan schedule:work
            echo "ATTENTION : le planificateur s est arrete, redemarrage dans 10 s."
            sleep 10
        done
    ' &
fi

exec apache2-foreground
