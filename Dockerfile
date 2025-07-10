# Utilise l'image PHP 8.2 avec Apache comme base.
FROM php:8.2-apache

# Installer les dépendances système nécessaires.
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libzip-dev \
    zip \
    build-essential \
    libonig-dev \
    nodejs \
    npm \
    dos2unix \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires.
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath opcache gd sockets zip

# Activer le module Apache 'rewrite' pour Laravel.
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copier composer files et installer les dépendances PHP
COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev --no-scripts

# Installer les dépendances JS
COPY package.json package-lock.json ./
RUN npm install

# Copier tout le code source
COPY . .

# Compiler les assets frontend avec Vite
RUN npm run build

# Créer le lien symbolique storage:link en root (au build time)
RUN php artisan storage:link

# Configurer Apache pour utiliser le dossier public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i '/<VirtualHost \*:80>/a\ \ \ \ <Directory ${APACHE_DOCUMENT_ROOT}>\n\ \ \ \ \ \ \ \ AllowOverride All\n\ \ \ \ \ \ \ \ Require all granted\n\ \ \ \ </Directory>' /etc/apache2/sites-available/000-default.conf

# Donne la propriété www-data sur storage, cache, public/storage ET vendor
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage /var/www/html/vendor

# Permissions pour Laravel (logs, cache, storage, vendor)
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage /var/www/html/vendor

# Commandes de debug (optionnelles, utiles en build)
RUN echo "--- Listing /var/www/html/ ---" && ls -la /var/www/html/
RUN echo "--- Listing /var/www/html/public/ ---" && ls -la /var/www/html/public/
RUN echo "--- Content of manifest.json ---" && cat /var/www/html/public/build/manifest.json || echo "manifest.json not found"
RUN echo "--- Content of calls.js ---" && cat /var/www/html/resources/js/calls.js || echo "calls.js not found"
RUN echo "--- Apache conf ---" && cat /etc/apache2/sites-available/000-default.conf

# Copier start.sh dans /var/www/html, convertir en Unix, donner les droits et changer propriétaire
COPY start.sh /var/www/html/start.sh
RUN dos2unix /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh
RUN chown www-data:www-data /var/www/html/start.sh
RUN echo "--- Permissions for start.sh ---" && ls -l /var/www/html/start.sh

EXPOSE 80

# Passer à l’utilisateur www-data
USER www-data

# Lancement du conteneur via le script start.sh dans /var/www/html
CMD ["/var/www/html/start.sh"]
