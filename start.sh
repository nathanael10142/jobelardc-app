#!/bin/bash

# Générer la clé d'application si elle n'existe pas (important pour la première exécution)
echo "Generating Laravel application key if not set..."
php artisan key:generate --force
if [ $? -ne 0 ]; then
    echo "Key generation failed! Exiting."
    exit 1
fi

# Vider TOUS les caches Laravel au début pour un état propre
echo "Clearing ALL Laravel caches for a fresh start..."
php artisan optimize:clear # Ceci inclut view:clear, cache:clear, config:clear, route:clear
if [ $? -ne 0 ]; then
    echo "Laravel cache clearing failed! Exiting."
    exit 1
fi

# Forcer la reconstruction de l'autochargement Composer de manière optimisée
echo "Dumping Composer autoload files with optimization..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
if [ $? -ne 0 ]; then
    echo "Composer dump-autoload failed! Exiting."
    exit 1
fi

# Reconstruire le cache de configuration et de routes APRÈS dump-autoload
echo "Building Laravel configuration and route caches..."
php artisan config:cache
if [ $? -ne 0 ]; then
    echo "Config cache failed! Exiting."
    exit 1
fi
php artisan route:cache
if [ $? -ne 0 ]; then
    echo "Route cache failed! Exiting."
    exit 1
fi

# Drop all tables, run migrations, and then run seeders
echo "Running Laravel migrations and seeders from a fresh database..."
php artisan migrate:fresh --seed --force
if [ $? -ne 0 ]; then
    echo "Migration and Seeding failed! Exiting."
    exit 1
fi

# Définir les permissions pour les répertoires de stockage et de cache
echo "Setting permissions for storage and bootstrap/cache directories..."
chmod -R 777 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 
if [ $? -ne 0 ]; then
    echo "Setting ownership failed! Exiting."
    exit 1
fi

# Nettoyer le cache de configuration et d'application juste avant de démarrer le queue worker
echo "Clearing config and application cache specifically for the queue worker..."
php artisan config:clear
php artisan cache:clear

# Démarrer le queue worker en arrière-plan
echo "Starting Laravel queue worker in background..."
php artisan queue:work --daemon --tries=3 &

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground
