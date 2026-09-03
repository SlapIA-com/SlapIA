FROM php:8.3-apache

# Extensions PHP utiles pour un site web classique (pas de base de données)
RUN apt-get update && apt-get install -y \
      libicu-dev libzip-dev libpng-dev unzip git \
 && docker-php-ext-install intl zip gd \
 && rm -rf /var/lib/apt/lists/*

# .htaccess : activer la réécriture + autoriser AllowOverride
RUN a2enmod rewrite headers \
 && sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf

# Passer APP_ENV à Apache (pour les règles .htaccess conditionnelles)
RUN echo 'PassEnv APP_ENV' > /etc/apache2/conf-enabled/passenv.conf

# Composer (génère l'autoloader)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction || true
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
