# Utilise l'image PHP 8.2 avec Apache comme base.
FROM php:8.2-apache

# Installer les dépendances système nécessaires.
# libonig-dev est une dépendance essentielle pour mbstring.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libzip-dev \
    zip \
    build-essential \
    libonig-dev \
    # Nettoie les fichiers de cache apt pour réduire la taille de l'image.
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires.
# L'ordre a peu d'importance tant que les dépendances système sont là.
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install opcache
# Si vous avez besoin de GD pour la manipulation d'images, décommentez la ligne suivante
# RUN docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp
# RUN docker-php-ext-install gd

# Activer le module Apache 'rewrite' pour les belles URLs de Laravel.
RUN a2enmod rewrite

# Copier le fichier .htaccess de Laravel vers le bon emplacement dans le serveur web.
COPY public/.htaccess /var/www/html/public/.htaccess

# Définir le répertoire racine du document Apache au dossier 'public' de Laravel.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier tous les fichiers de l'application Laravel dans le répertoire du serveur web.
COPY . /var/www/html

# Définir le répertoire de travail par défaut pour les commandes futures dans le conteneur.
WORKDIR /var/www/html

# Installer Composer si ce n'est pas déjà fait dans l'image de base.
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Installer les dépendances Composer de Laravel.
RUN composer install --no-dev --optimize-autoloader

# Exécuter les commandes d'optimisation de Laravel pour la production.
RUN php artisan optimize:clear
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Définir les permissions correctes pour les dossiers 'storage' et 'bootstrap/cache'.
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Exposer le port 80 pour qu'Apache puisse recevoir des requêtes web.
EXPOSE 80
