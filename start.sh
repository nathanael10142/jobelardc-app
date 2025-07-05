#!/bin/bash

# --- Préparation Initiale de l'Environnement ---

# Générer la clé d'application si elle n'existe pas (important pour la première exécution)
echo "Generating Laravel application key if not set..."
php artisan key:generate --force
if [ $? -ne 0 ]; then
    echo "ERROR: Key generation failed! Exiting."
    exit 1
fi

# Vider TOUS les caches Laravel au début pour garantir que les nouvelles configurations (ex: broadcasting.php, session.php) sont prises en compte
# php artisan optimize:clear inclut déjà les autres clears (config, cache, route, view)
echo "Clearing ALL Laravel caches for a fresh start (config, route, cache, view)..."
php artisan optimize:clear
if [ $? -ne 0 ]; then
    echo "ERROR: Laravel cache clearing failed! Exiting."
    exit 1
fi

# Forcer la reconstruction de l'autochargement Composer de manière optimisée
echo "Dumping Composer autoload files with optimization..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
if [ $? -ne 0 ]; then
    echo "ERROR: Composer dump-autoload failed! Exiting."
    exit 1
fi

# Reconstruire le cache de configuration et de routes APRÈS dump-autoload et clear des caches précédents
# C'est crucial pour s'assurer que les fichiers de config récemment ajoutés/modifiés sont inclus.
echo "Building Laravel configuration and route caches..."
php artisan config:cache
if [ $? -ne 0 ]; then
    echo "ERROR: Config cache failed! Exiting."
    exit 1
fi
php artisan route:cache
if [ $? -ne 0 ]; then
    echo "ERROR: Route cache failed! Exiting."
    exit 1
fi

# --- Opérations Base de Données ---

# Drop all tables, run migrations, and then run seeders
echo "Running Laravel migrations and seeders from a fresh database..."
php artisan migrate:fresh --seed --force
if [ $? -ne 0 ]; then
    echo "ERROR: Migration and Seeding failed! Exiting."
    exit 1
fi

# --- Gestion des Permissions ---

# Définir les permissions pour les répertoires de stockage et de cache
echo "Setting permissions for storage and bootstrap/cache directories..."
chmod -R 777 storage bootstrap/cache
# Il est important de s'assurer que www-data est l'utilisateur sous lequel Apache/PHP s'exécute.
# Sur Render.com avec l'image PHP/Apache, c'est généralement le cas.
chown -R www-data:www-data storage bootstrap/cache
# La vérification de l'ownership est un peu plus complexe car chown ne renvoie pas toujours une erreur directe si l'utilisateur n'existe pas.
# Mais pour un environnement Docker standard, cela devrait fonctionner.
# if [ $? -ne 0 ]; then
#     echo "WARNING: Setting ownership might have failed or user www-data does not exist. Please check logs."
# fi


# --- Démarrage des Services Applicatifs ---

# Optionnel : Nettoyer le cache de configuration et d'application juste avant de démarrer le queue worker
# Dans un environnement avec config:cache, config:clear peut casser le cache.
# L'idée est de s'assurer que le worker a la configuration la plus à jour, mais si config:cache est fait,
# il est préférable de ne pas faire config:clear après. Cependant, pour le débogage, on le garde.
echo "Clearing config and application cache specifically for the queue worker (optional, can be removed if config:cache is sufficient)..."
php artisan config:clear
php artisan cache:clear

# Ajout de la commande de débogage pour le schéma de la table 'jobs'
echo "--- Checking 'jobs' table schema before starting queue worker ---"
# Utilise un timeout pour éviter de bloquer indéfiniment si la DB n'est pas accessible
timeout 10s php artisan db:table jobs --dump-schema || echo "WARNING: Command 'php artisan db:table jobs --dump-schema' failed or timed out."
echo "--- End of 'jobs' table schema check ---"

# Démarrer le queue worker en arrière-plan
echo "Starting Laravel queue worker in background..."
php artisan queue:work --daemon --tries=3 &
# Ajout d'une petite pause pour permettre au worker de démarrer et logguer d'éventuelles erreurs initiales.
sleep 5

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground