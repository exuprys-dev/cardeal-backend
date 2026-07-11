FROM php:8.2-fpm

# Installer les dépendances système et PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

# Installer les extensions PHP indispensables pour Laravel et MySQL
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le code du projet
WORKDIR /var/www
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Configurer les permissions pour le stockage de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Configurer Nginx
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

EXPOSE 80

CMD php artisan config:cache && php artisan route:cache && nginx -g "daemon off;"