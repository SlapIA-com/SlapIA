# syntax=docker/dockerfile:1
#
# Remplace l'ancien Dockerfile (php:8.2-apache + COPY brut) : l'app Laravel a
# besoin d'un build Node (assets Vite/React) et d'un `composer install`
# avant de servir quoi que ce soit. Multi-stage pour ne pas embarquer Node
# ni les devDependencies dans l'image finale.
#
# Garde le même nom de service dans docker-compose.yml (slapia-prod /
# slapia-dev) — seul le contenu du Dockerfile change.

# ---- Stage 1 : build des assets front (React/Inertia via Vite) ----
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY resources/ resources/
COPY vite.config.ts tailwind.config.js postcss.config.js tsconfig.json ./
RUN npm run build

# ---- Stage 2 : dépendances PHP (composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 3 : runtime PHP + Apache ----
FROM php:8.2-apache
# libonig-dev : requis pour compiler mbstring (regex Oniguruma), absent de
# l'image php:8.2-apache de base.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mysqli mbstring bcmath exif pcntl opcache \
    && a2enmod rewrite

# Laravel sert depuis public/, pas la racine du dépôt.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
