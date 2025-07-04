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
php artisan optimize:clear
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

# NOUVEAU: Définir les permissions pour les répertoires de stockage et de cache
# Cela permet au serveur web d'écrire dans ces répertoires.
echo "Setting permissions for storage and bootstrap/cache directories..."

# --- COMMANDES DE DÉBOGAGE AJOUTÉES ---
echo "--- Before permissions change ---"
echo "Current user: $(whoami)"
ls -la storage bootstrap/cache
id www-data || echo "www-data user not found or id command failed" # Vérifie si www-data existe
# --- FIN COMMANDES DE DÉBOGAGE ---

# TEMPORAIREMENT CHMOD 777 POUR LE DÉBOGAGE DES PERMISSIONS
chmod -R 777 storage bootstrap/cache
# Changer le propriétaire des répertoires pour l'utilisateur Apache (www-data)
chown -R www-data:www-data storage bootstrap/cache 
if [ $? -ne 0 ]; then
    echo "Setting ownership failed! Exiting."
    exit 1
fi

# --- COMMANDES DE DÉBOGAGE APRÈS CHANGEMENT DE PERMISSIONS ---
echo "--- After permissions change ---"
ls -la storage bootstrap/cache
# --- FIN COMMANDES DE DÉBOGAGE ---


# Démarrer le queue worker en arrière-plan
echo "Starting Laravel queue worker in background..."
php artisan queue:work --daemon --tries=3 &

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground
