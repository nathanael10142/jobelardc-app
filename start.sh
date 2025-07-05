#!/bin/bash

echo "--- Starting Jobela RDC Deployment Script ---"

# --- 1. Préparation Initiale de l'Environnement ---

# Générer la clé d'application si elle n'existe pas. C'est crucial pour la sécurité et le chiffrement.
echo "1. Generating Laravel application key if not set..."
php artisan key:generate --force
if [ $? -ne 0 ]; then
    echo "ERROR: Key generation failed! This is critical. Exiting."
    exit 1
fi

# Vider et reconstruire TOUS les caches Laravel pour garantir que les nouvelles configurations
# (comme config/broadcasting.php, config/session.php et .env) sont prises en compte.
# 'optimize:clear' fait déjà le travail de 'config:clear', 'cache:clear', 'route:clear', 'view:clear'.
echo "2. Clearing ALL Laravel caches for a fresh start (config, route, cache, view)..."
php artisan optimize:clear
if [ $? -ne 0 ]; then
    echo "ERROR: Laravel cache clearing failed! Exiting."
    exit 1
fi

# Forcer la reconstruction de l'autochargement Composer de manière optimisée.
# Ceci s'assure que toutes les classes sont correctement chargées, surtout après des ajouts/modifications.
echo "3. Dumping Composer autoload files with optimization..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
if [ $? -ne 0 ]; then
    echo "ERROR: Composer dump-autoload failed! Exiting."
    exit 1
fi

# Reconstruire spécifiquement le cache de configuration et de routes APRÈS le nettoyage et l'autoload Composer.
# Cela garantit que la configuration et les routes sont compilées à partir des fichiers les plus récents.
echo "4. Building Laravel configuration and route caches..."
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

# --- 2. Opérations Base de Données ---

# Effacer toutes les tables, exécuter les migrations, puis lancer les seeders.
# Assure un état de base de données propre à chaque déploiement.
echo "5. Running Laravel migrations and seeders from a fresh database..."
php artisan migrate:fresh --seed --force
if [ $? -ne 0 ]; then
    echo "ERROR: Migration and Seeding failed! This is critical. Exiting."
    exit 1
fi

# --- 3. Gestion des Permissions du Système de Fichiers ---

# Définir les permissions pour les répertoires de stockage et de cache.
# 'storage' est essentiel pour les logs, sessions, uploads. 'bootstrap/cache' pour les fichiers de cache compilés.
echo "6. Setting permissions for storage and bootstrap/cache directories..."
chmod -R 777 storage bootstrap/cache
# Changer la propriété des fichiers/dossiers à l'utilisateur 'www-data', typique des environnements PHP/Apache.
chown -R www-data:www-data storage bootstrap/cache
# Note: La vérification directe du succès de chown est complexe. On assume le succès si chmod réussit.

# --- 4. Démarrage des Services Applicatifs ---

# Nettoyer le cache d'application et de configuration avant de démarrer le queue worker.
# Ceci est une précaution supplémentaire pour le worker, car il tourne en arrière-plan.
echo "7. Clearing config and application cache specifically for the queue worker..."
php artisan config:clear
php artisan cache:clear

# Démarrer le queue worker en arrière-plan. Il est essentiel pour les tâches asynchrones comme les notifications Pusher.
echo "8. Starting Laravel queue worker in background..."
php artisan queue:work --daemon --tries=3 &
# Ajouter une petite pause pour permettre au worker de s'initialiser et de logguer d'éventuelles erreurs de démarrage.
sleep 5

# Démarrer le serveur Apache.
echo "9. Starting Apache in foreground..."
apache2-foreground

echo "--- Jobela RDC Deployment Script Finished ---"