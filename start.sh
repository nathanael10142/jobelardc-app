#!/bin/bash

# Exécuter les migrations Laravel
echo "Running Laravel migrations..."
php artisan migrate --force

# Vérifier si les migrations ont réussi
if [ $? -ne 0 ]; then
    echo "Migrations failed! Exiting."
    exit 1
fi

# Exécuter les seeders (pour les données initiales)
echo "Running Laravel seeders..."
php artisan db:seed

# Vérifier si les seeders ont réussi
if [ $? -ne 0 ]; then
    echo "Seeders failed! Exiting."
    exit 1
fi

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground
