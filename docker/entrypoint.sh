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
export APP_KEY
if ! printf '%s' "$APP_KEY" | grep -Eq '^base64:[A-Za-z0-9+/]{43}=$'; then
    echo "ATTENTION : APP_KEY invalide ou absente. Attendu : base64: suivi de 44 caractères (se terminant par =)."
fi

# Certificat CA de la base MySQL (Aiven), fourni en variable d'environnement
if [ -n "${DB_SSL_CA_PEM:-}" ]; then
    printf '%s\n' "$DB_SSL_CA_PEM" > /etc/ssl/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/etc/ssl/db-ca.pem
fi

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

exec apache2-foreground
