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

# Configurer Apache pour utiliser le dossier public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i '/<VirtualHost \*:80>/a\ \ \ \ <Directory ${APACHE_DOCUMENT_ROOT}>\n\ \ \ \ \ \ \ \ AllowOverride All\n\ \ \ \ \ \ \ \ Require all granted\n\ \ \ \ </Directory>' /etc/apache2/sites-available/000-default.conf

# Permissions pour Laravel (logs, cache)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Commandes de debug (optionnelles, utiles en build)
RUN echo "--- Listing /var/www/html/ ---" && ls -la /var/www/html/
RUN echo "--- Listing /var/www/html/public/ ---" && ls -la /var/www/html/public/
RUN echo "--- Content of manifest.json ---" && cat /var/www/html/public/build/manifest.json || echo "manifest.json not found"
RUN echo "--- Content of calls.js ---" && cat /var/www/html/resources/js/calls.js || echo "calls.js not found"
RUN echo "--- Apache conf ---" && cat /etc/apache2/sites-available/000-default.conf

# Copier start.sh, convertir en Unix, donner les droits et changer propriétaire
COPY start.sh /usr/local/bin/start.sh
RUN dos2unix /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
RUN chown www-data:www-data /usr/local/bin/start.sh
RUN echo "--- Permissions for start.sh ---" && ls -l /usr/local/bin/start.sh

EXPOSE 80

# Passer à l’utilisateur www-data
USER www-data

# Lancement du conteneur via le script start.sh directement (doit être exécutable)
CMD ["/usr/local/bin/start.sh"]
