# Utilise l'image PHP 8.2 avec Apache comme base.
FROM php:8.2-apache

# Installer les dépendances système nécessaires.
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
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install exif
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install opcache

# Activer le module Apache 'rewrite' pour les belles URLs de Laravel.
RUN a2enmod rewrite

# Copier le fichier .htaccess de Laravel vers le bon emplacement dans le serveur web.
COPY public/.htaccess /var/www/html/public/.htaccess

# Définir le répertoire racine du document Apache au dossier 'public' de Laravel.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# MODIFICATION CRUCIALE : Assurer la configuration correcte d'Apache pour Laravel
# Mettre à jour le DocumentRoot dans le fichier de configuration par défaut d'Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Ajouter une directive Directory pour le dossier public pour permettre la réécriture d'URL via .htaccess
# Cela garantit que les règles de réécriture dans public/.htaccess sont respectées.
RUN echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

# Copier tous les fichiers de l'application Laravel dans le répertoire du serveur web.
COPY . /var/www/html

# AJOUT DE COMMANDES DE DÉBOGAGE POUR VÉRIFIER LES FICHIERS ET LA CONFIGURATION APACHE
RUN echo "--- Listing /var/www/html/ ---" && ls -la /var/www/html/
RUN echo "--- Listing /var/www/html/public/ ---" && ls -la /var/www/html/public/
RUN echo "--- Content of /var/www/html/routes/web.php ---" && cat /var/www/html/routes/web.php || echo "routes/web.php not found"
RUN echo "--- Content of /etc/apache2/sites-available/000-default.conf ---" && cat /etc/apache2/sites-available/000-default.conf
RUN echo "--- Content of /etc/apache2/apache2.conf ---" && cat /etc/apache2/apache2.conf

# Copier le script de démarrage et le rendre exécutable
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# Définir le répertoire de travail par défaut pour les commandes futures dans le conteneur.
WORKDIR /var/www/html

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copier .env.example vers .env (nécessaire pour certaines étapes de build)
RUN cp .env.example .env

# MODIFICATION CRUCIALE : Exclure les dépendances de développement lors de l'installation de Composer
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev --no-scripts

# Définir les permissions correctes
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Exposer le port 80
EXPOSE 80
