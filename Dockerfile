# Utilise l'image PHP 8.2 avec Apache comme base.
# C'est une excellente base pour les applications Laravel.
FROM php:8.2-apache

# Installer les dépendances système nécessaires.
# git: pour les dépendances Composer qui peuvent venir de dépôts Git.
# unzip: pour décompresser les dépendances Composer.
# libpq-dev: nécessaire pour l'extension pdo_pgsql (connexion à PostgreSQL).
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    # Nettoie les fichiers de cache apt pour réduire la taille de l'image.
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires.
# pdo_pgsql: pour la connexion à la base de données PostgreSQL de Render.com.
# mbstring: pour la manipulation des chaînes de caractères multibyte.
# exif: pour la lecture des données EXIF des images.
# pcntl: pour les commandes Artisan (peut être nécessaire pour certaines).
# bcmath: pour les opérations mathématiques de précision.
# opcache: pour améliorer les performances de PHP en cacheant le bytecode.
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath opcache

# Activer le module Apache 'rewrite' pour les belles URLs de Laravel.
RUN a2enmod rewrite

# Copier le fichier .htaccess de Laravel vers le bon emplacement dans le serveur web.
# Le .htaccess de Laravel se trouve dans le dossier 'public'.
COPY public/.htaccess /var/www/html/public/.htaccess

# Définir le répertoire racine du document Apache au dossier 'public' de Laravel.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APCHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copier tous les fichiers de l'application Laravel dans le répertoire du serveur web.
# Le '.' indique le répertoire courant de l'hôte (votre projet Laravel).
# '/var/www/html' est le répertoire de travail par défaut pour Apache dans cette image.
COPY . /var/www/html

# Définir le répertoire de travail par défaut pour les commandes futures dans le conteneur.
WORKDIR /var/www/html

# Installer Composer si ce n'est pas déjà fait dans l'image de base (bonne pratique).
# On le copie depuis une image composer officielle pour s'assurer d'avoir la dernière version.
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Installer les dépendances Composer de Laravel.
# --no-dev: exclut les dépendances de développement (pour une image de production plus légère).
# --optimize-autoloader: optimise l'autoloader pour de meilleures performances en production.
RUN composer install --no-dev --optimize-autoloader

# Exécuter les commandes d'optimisation de Laravel pour la production.
# Cela met en cache la configuration, les routes et les vues, améliorant les performances.
RUN php artisan optimize:clear
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Définir les permissions correctes pour les dossiers 'storage' et 'bootstrap/cache'.
# Cela permet à l'utilisateur Apache (www-data) d'écrire dans ces dossiers, ce qui est essentiel pour Laravel.
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Exposer le port 80 pour qu'Apache puisse recevoir des requêtes web.
EXPOSE 80
