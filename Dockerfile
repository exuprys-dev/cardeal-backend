FROM php:8.4-cli

# Installer les extensions et outils requis
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Installer l'extension pdo_mysql pour la base Aiven
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Récupérer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Installer les dépendances Laravel sans les packages de dev
RUN composer install --no-dev --optimize-autoloader

# Droits d'écriture pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Port sur lequel Render va écouter
EXPOSE 80

# Démarrer le serveur interne de PHP sur le port 80
# Démarrer les migrations automatiquement puis lancer le serveur interne de PHP
CMD php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=80