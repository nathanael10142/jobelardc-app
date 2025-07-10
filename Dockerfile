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
    # Nettoie les fichiers de cache apt pour réduire la taille de l'image.
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires.
# Groupé pour une meilleure performance de build.
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath opcache gd sockets zip

# Activer le module Apache 'rewrite' pour les belles URLs de Laravel.
RUN a2enmod rewrite

# Définir le répertoire de travail par défaut pour les commandes futures dans le conteneur.
WORKDIR /var/www/html

# Copier les fichiers composer.json et composer.lock
# Cela permet à Docker de mettre en cache la couche `composer install`
# si ces fichiers ne changent pas.
COPY composer.json composer.lock ./

# Installer Composer (gestionnaire de dépendances PHP)
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Exécuter les commandes Composer
# --no-dev : ne pas installer les dépendances de développement en production.
# --optimize-autoloader : optimise l'autochargement des classes.
# --no-scripts : évite d'exécuter des scripts post-install qui pourraient échouer
#               car l'environnement n'est pas encore complètement prêt ou `.env` n'est pas encore généré.
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev --no-scripts

# Copier les fichiers package.json et package-lock.json pour installer les dépendances JS.
COPY package.json package-lock.json ./

# Installer les dépendances Node.js
RUN npm install

# Copier tous les fichiers de l'application (le reste du code source)
# Cette étape doit se faire APRÈS les installations de dépendances Composer et NPM
# pour tirer parti du cache Docker.
COPY . .

# Compiler les assets frontend avec Vite
# `npm run build` génère des fichiers optimisés pour la production et le manifest.json.
# Ceci est CRUCIAL pour l'erreur "Unable to locate file in Vite manifest".
RUN npm run build

# Définir le répertoire racine du document Apache au dossier 'public' de Laravel.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Mettre à jour le DocumentRoot et ajouter AllowOverride All dans le fichier de configuration par défaut d'Apache.
# C'est essentiel pour que Laravel fonctionne correctement avec Apache et les .htaccess.
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -i '/<VirtualHost \*:80>/a\ \ \ \ <Directory ${APACHE_DOCUMENT_ROOT}>\n\ \ \ \ \ \ \ \ AllowOverride All\n\ \ \ \ \ \ \ \ Require all granted\n\ \ \ \ </Directory>' /etc/apache2/sites-available/000-default.conf


# Définir les permissions correctes pour les répertoires de Laravel.
# Cela permet à Laravel d'écrire dans les logs et le cache.
# Le start.sh le fera aussi au runtime, c'est une bonne redondance.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# AJOUT DE COMMANDES DE DÉBOGAGE POUR VÉRIFIER LES FICHIERS ET LA CONFIGURATION APACHE
# Ces RUN commandes s'exécuteront pendant le BUILD sur Render.com.
# Elles seront visibles dans les Build Logs et aideront à vérifier si les fichiers sont là.
RUN echo "--- Listing /var/www/html/ ---" && ls -la /var/www/html/
RUN echo "--- Listing /var/www/html/public/ ---" && ls -la /var/www/html/public/
RUN echo "--- Content of /var/www/html/public/build/manifest.json ---" && cat /var/www/html/public/build/manifest.json || echo "manifest.json not found in build directory"
RUN echo "--- Content of /var/www/html/resources/js/calls.js (source) ---" && cat /var/www/html/resources/js/calls.js || echo "resources/js/calls.js not found"
RUN echo "--- Content of /etc/apache2/sites-available/000-default.conf ---" && cat /etc/apache2/sites-available/000-default.conf

# Copier le script de démarrage et le rendre exécutable
# Placé dans /usr/local/bin qui est dans le PATH par défaut.
COPY start.sh /usr/local/bin/start.sh
RUN dos2unix /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
# Changer le propriétaire du script de démarrage à www-data
RUN chown www-data:www-data /usr/local/bin/start.sh
RUN echo "--- Permissions for start.sh ---" && ls -l /usr/local/bin/start.sh

# Exposer le port 80 (Apache par défaut)
EXPOSE 80

# Changer l'utilisateur à www-data pour l'exécution du CMD
USER www-data

# Définir la commande d'entrée principale pour le conteneur
# Utilise votre script start.sh comme point d'entrée
CMD ["sh", "/usr/local/bin/start.sh"]
