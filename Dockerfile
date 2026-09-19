# syntax=docker/dockerfile:1
#
# Image de démonstration (hébergement gratuit type Render).
# Les dépendances de développement sont volontairement conservées : les seeders
# de démo utilisent Faker. Pour une vraie production, voir DEPLOYMENT.md.

# ---- 1. Dépendances PHP ------------------------------------------------------
# Aussi nécessaires à l'étape 2 : Tailwind scanne des vues situées dans vendor/.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist --no-progress --ignore-platform-reqs

# ---- 2. Assets front (Vite + Tailwind) ---------------------------------------
FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---- 3. Application PHP + Apache ---------------------------------------------
FROM php:8.3-apache

COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql bcmath gd zip exif opcache

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache : racine sur public/, réécriture d'URL (.htaccess de Laravel) et peu de
# processus pour rester dans les 512 Mo de l'offre gratuite.
RUN a2enmod rewrite headers \
 && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!' /etc/apache2/sites-available/000-default.conf \
 && printf '%s\n' \
      'ServerName localhost' \
      '<Directory /var/www/html/public>' \
      '    AllowOverride All' \
      '    Require all granted' \
      '</Directory>' \
      '<IfModule mpm_prefork_module>' \
      '    StartServers 2' \
      '    MinSpareServers 2' \
      '    MaxSpareServers 4' \
      '    MaxRequestWorkers 12' \
      '</IfModule>' \
      > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-interaction \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=10000
EXPOSE 10000
CMD ["/usr/local/bin/entrypoint.sh"]
