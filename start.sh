#!/bin/bash

# Générer la clé d'application si elle n'existe pas (important pour la première exécution)
# La clé sera lue depuis les variables d'environnement de Render si définie,
# sinon elle sera générée dans le .env du conteneur.
echo "Generating Laravel application key if not set..."
php artisan key:generate --force

# Vérifier si la génération de clé a réussi
if [ $? -ne 0 ]; then
    echo "Key generation failed! Exiting."
    exit 1
fi

# MODIFICATION ICI : Forcer la reconstruction de l'autochargement Composer
echo "Dumping Composer autoload files..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload

# Vérifier si dump-autoload a réussi
if [ $? -ne 0 ]; then
    echo "Composer dump-autoload failed! Exiting."
    exit 1
fi

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
