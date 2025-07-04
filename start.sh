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

# MODIFICATION ICI : Vider TOUS les caches Laravel au début pour un état propre
echo "Clearing ALL Laravel caches for a fresh start..."
php artisan optimize:clear # Ceci inclut view:clear, cache:clear, config:clear, route:clear
if [ $? -ne 0 ]; then
    echo "Laravel cache clearing failed! Exiting."
    exit 1
fi

# MODIFICATION ICI : Forcer la reconstruction de l'autochargement Composer de manière optimisée
echo "Dumping Composer autoload files with optimization..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
# --optimize pour optimiser l'autochargement pour la production
# --no-dev pour exclure les dépendances de développement de l'autochargement

# Vérifier si dump-autoload a réussi
if [ $? -ne 0 ]; then
    echo "Composer dump-autoload failed! Exiting."
    exit 1
fi

# MODIFICATION ICI : Reconstruire le cache de configuration et de routes APRÈS dump-autoload
# Ces caches sont importants pour la performance et le bon fonctionnement de Laravel
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
# Utilise migrate:fresh --seed --force pour une réinitialisation complète de la base de données
# Cela supprime toutes les tables, exécute les migrations, puis les seeders.
php artisan migrate:fresh --seed --force

# Vérifier si migrate:fresh --seed a réussi
if [ $? -ne 0 ]; then
    echo "Migration and Seeding failed! Exiting."
    exit 1
fi

# NOUVEAU: Démarrer le queue worker en arrière-plan
# Le --daemon permet au worker de continuer à s'exécuter en arrière-plan.
# --tries=3 tente de traiter une tâche 3 fois avant de la marquer comme échouée.
# Le "&" à la fin est CRUCIAL pour que la commande s'exécute en arrière-plan
# et que le script puisse continuer à démarrer le serveur web.
echo "Starting Laravel queue worker in background..."
php artisan queue:work --daemon --tries=3 &

# Démarrer le serveur Apache
echo "Starting Apache..."
apache2-foreground
