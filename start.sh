#!/bin/bash

# Drop all tables, run migrations, and then run seeders
echo "Running Laravel migrations and seeders from a fresh database..."
# Utilise migrate:fresh --seed --force pour une réinitialisation complète de la base de données
# Cela supprime toutes les tables, exécute les migrations, puis les seeders.
php artisan migrate:fresh --seed --force

# Vérifier si migrate:fresh --seed a réussi
if [ $? -ne 0 ]; then
    echo "Migration and Seeding failed! Exiting."
    exit 1
fi

# Vider tous les caches Laravel
echo "Clearing Laravel caches..."
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground
