FROM php:8.2-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    # Ajoutez d'autres extensions PHP si votre projet en a besoin (ex: gd, imagick, pdo_mysql si vous voulez MySQL malgré PostgreSQL par défaut)
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath opcache

# Configurer Apache pour pointer vers le dossier public de Laravel
RUN a2enmod rewrite
COPY .htaccess /var/www/html/public/.htaccess
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier le code de l'application
COPY . /var/www/html

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer Composer si ce n'est pas déjà fait (souvent déjà dans le Dockerfile PHP)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Installer les dépendances Composer
# On ne met pas --no-dev ici pour laisser Render décider (il fera souvent une install propre)
RUN composer install --no-dev --optimize-autoloader

# Nettoyage des caches Laravel
RUN php artisan optimize:clear
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# S'assurer que les permissions du dossier storage sont correctes
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 80
