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
    nodejs \
    npm \
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
# IMPORTANT: Ceci doit être fait avant de copier le reste de l'app si .htaccess est dans public/
COPY public/.htaccess /var/www/html/public/.htaccess

# Définir le répertoire racine du document Apache au dossier 'public' de Laravel.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# MODIFICATION CRUCIALE : Assurer la configuration correcte d'Apache pour Laravel
# Mettre à jour le DocumentRoot dans le fichier de configuration par défaut d'Apache
# Utilisez 000-default.conf, qui est le fichier de site par défaut d'Apache
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# AJOUT : Assurer AllowOverride All pour le DocumentRoot spécifique dans 000-default.conf
# Cette commande insère le bloc <Directory> juste après <VirtualHost *:80>
RUN sed -i '/<VirtualHost \*:80>/a\ \ \ \ <Directory ${APACHE_DOCUMENT_ROOT}>\n\ \ \ \ \ \ \ \ AllowOverride All\n\ \ \ \ \ \ \ \ Require all granted\n\ \ \ \ </Directory>' /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail par défaut pour les commandes futures dans le conteneur.
WORKDIR /var/www/html

# Copier TOUS les fichiers de l'application Laravel dans le répertoire du serveur web.
# C'est CRUCIAL pour que 'npm run build' trouve les fichiers dans 'resources/'
COPY . .

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Exécuter les commandes Composer (maintenant que tous les fichiers sont là)
# MODIFICATION CRUCIALE : Exclure les dépendances de développement lors de l'installation de Composer
# Utilisez --no-scripts pour éviter les problèmes si des scripts Composer dépendent de Node.js non encore installé.
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev --no-scripts

# Déclarer les arguments de build pour les variables VITE_
# Render.com injectera les variables d'environnement définies dans ses settings ici.
ARG VITE_PUSHER_APP_KEY
ARG VITE_PUSHER_APP_CLUSTER

# Installer les dépendances Node.js et compiler les assets frontend avec Vite
# Maintenant que 'resources/' est copié, cette commande devrait trouver app.css/app.js
RUN npm install && npm run build

# Définir les permissions correctes
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copier .env.example vers .env (nécessaire pour certaines étapes de build si non déjà fait)
# C'est mieux de le faire ici, après avoir copié tout le reste du code.
RUN cp .env.example .env

# AJOUT DE COMMANDES DE DÉBOGAGE POUR VÉRIFIER LES FICHIERS ET LA CONFIGURATION APACHE
RUN echo "--- Listing /var/www/html/ ---" && ls -la /var/www/html/
RUN echo "--- Listing /var/www/html/public/ ---" && ls -la /var/www/html/public/
RUN echo "--- Content of /var/www/html/public/build/manifest.json ---" && cat /var/www/html/public/build/manifest.json || echo "manifest.json not found in build directory"
RUN echo "--- Content of /var/www/html/resources/css/app.css ---" && cat /var/www/html/resources/css/app.css || echo "resources/css/app.css not found"
RUN echo "--- Content of /var/www/html/routes/web.php ---" && cat /var/www/html/routes/web.php || echo "routes/web.php not found"
RUN echo "--- Content of /etc/apache2/sites-available/000-default.conf ---" && cat /etc/apache2/sites-available/000-default.conf
RUN echo "--- Content of /etc/apache2/apache2.conf ---" && cat /etc/apache2/apache2.conf

# Copier le script de démarrage et le rendre exécutable
COPY start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh

# Exposer le port 80 (Apache par défaut)
EXPOSE 80

# Définir la commande d'entrée principale pour le conteneur
# Utilise votre script start.sh comme point d'entrée
CMD ["/var/www/html/start.sh"]