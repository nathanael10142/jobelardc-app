#!/bin/bash

# Abort on any error
set -e

echo "--- Démarrage du script de déploiement Jobela RDC ---"

# --- 1. Préparation de l'environnement et du fichier .env ---

# Render injecte les variables d'environnement dans l'environnement du conteneur.
# Nous devons les écrire dans le fichier .env pour que Laravel puisse les lire.
# Assurez-vous que TOUTES vos variables d'environnement nécessaires sont définies dans les settings de Render !
echo "1. Écriture du fichier .env à partir des variables d'environnement de Render..."
# Nettoyer l'ancien .env s'il existe pour éviter les doublons
rm -f .env
# Filtrer les variables d'environnement pertinentes et les écrire dans .env
# Utilisez grep -E pour inclure toutes les variables que Laravel pourrait utiliser.
printenv | grep -E 'APP_|DB_|MAIL_|PUSHER_|BROADCAST_|QUEUE_|SESSION_|LOG_CHANNEL|FILESYSTEM_DISK|VITE_|MIX_|SANCTUM_' | while read -r line; do
  echo "$line" >> .env
done

# Générer la clé d'application si elle n'est pas déjà définie dans le .env (via Render).
# C'est crucial pour la sécurité et le chiffrement.
if ! grep -q "APP_KEY=" .env; then
  echo "2. APP_KEY non trouvée, génération d'une nouvelle clé d'application..."
  php artisan key:generate --show --force >> .env
  if [ $? -ne 0 ]; then
    echo "ERREUR: La génération de la clé d'application a échoué ! Ceci est critique. Arrêt du déploiement."
    exit 1
  fi
fi

# Créer le lien symbolique pour le stockage public.
# Essentiel si vous stockez des fichiers uploadés (avatars, etc.) localement.
echo "3. Création du lien symbolique pour le stockage public..."
php artisan storage:link --force
if [ $? -ne 0 ]; then
  echo "AVERTISSEMENT: La création du lien de stockage a échoué. Les fichiers uploadés pourraient ne pas être accessibles."
  # Ne pas sortir ici, car ce n'est pas toujours critique pour le démarrage de l'app.
fi


# --- 2. Opérations de base de données ---

# Exécuter les migrations de base de données.
# Utilisez 'php artisan migrate --force' en production pour ne pas perdre les données existantes.
# N'utilisez PAS 'migrate:fresh' en production sauf si vous voulez effacer toutes les données à chaque déploiement.
echo "4. Exécution des migrations de base de données..."
php artisan migrate --force
if [ $? -ne 0 ]; then
  echo "ERREUR: Les migrations de base de données ont échoué ! Ceci est critique. Arrêt du déploiement."
  exit 1
fi

# Si vous avez besoin d'exécuter des seeders spécifiques en production (rare), faites-le ici :
# echo "5. Exécution des seeders de production (si nécessaire)..."
# php artisan db:seed --class=YourProductionSeederClass --force


# --- 3. Optimisation et Caching Laravel ---

# Vider et reconstruire TOUS les caches Laravel.
# 'optimize:clear' fait déjà le travail de 'config:clear', 'cache:clear', 'route:clear', 'view:clear'.
echo "6. Nettoyage et reconstruction des caches Laravel (config, route, cache, view)..."
php artisan optimize:clear
if [ $? -ne 0 ]; then
  echo "ERREUR: Le nettoyage des caches Laravel a échoué ! Arrêt du déploiement."
  exit 1
fi

# Forcer la reconstruction de l'autochargement Composer de manière optimisée.
echo "7. Reconstruction de l'autochargement Composer..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
if [ $? -ne 0 ]; then
  echo "ERREUR: La reconstruction de l'autochargement Composer a échoué ! Arrêt du déploiement."
  exit 1
fi

# Reconstruire spécifiquement le cache de configuration et de routes APRÈS le nettoyage et l'autoload Composer.
# Cela garantit que la configuration et les routes sont compilées à partir des fichiers les plus récents.
echo "8. Construction des caches de configuration et de routes Laravel..."
php artisan config:cache
if [ $? -ne 0 ]; then
  echo "ERREUR: La mise en cache de la configuration a échoué ! Arrêt du déploiement."
  exit 1
fi
php artisan route:cache
if [ $? -ne 0 ]; then
  echo "ERREUR: La mise en cache des routes a échoué ! Arrêt du déploiement."
  exit 1
fi


# --- 4. Gestion des Permissions du Système de Fichiers ---

# Définir les permissions pour les répertoires de stockage et de cache.
echo "9. Définition des permissions pour les répertoires storage et bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
# Note: La vérification directe du succès de chown est complexe. On assume le succès si chmod réussit.


# --- 5. Démarrage du serveur web ---

# Démarrer le serveur Apache en premier plan.
# C'est la commande principale qui doit rester active pour que le service Render fonctionne.
echo "10. Démarrage du serveur Apache en premier plan..."
exec apache2-foreground

echo "--- Script de déploiement Jobela RDC terminé ---"
