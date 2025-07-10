#!/bin/bash

# Abort on any error
set -e

echo "--- Démarrage du script de déploiement Jobela RDC ---"

# --- 1. Préparation de l'environnement et du fichier .env ---

echo "1. Écriture du fichier .env à partir des variables d'environnement de Render..."
rm -f .env
printenv | grep -E 'APP_|DB_|MAIL_|PUSHER_|BROADCAST_|QUEUE_|SESSION_|LOG_CHANNEL|FILESYSTEM_DISK|VITE_|MIX_|SANCTUM_' | while read -r line; do
  echo "$line" >> .env
done

if ! grep -q "APP_KEY=" .env; then
  echo "2. APP_KEY non trouvée, génération d'une nouvelle clé d'application..."
  php artisan key:generate --show --force >> .env
  if [ $? -ne 0 ]; then
    echo "ERREUR: La génération de la clé d'application a échoué ! Ceci est critique. Arrêt du déploiement."
    exit 1
  fi
fi

echo "3. Création du lien symbolique pour le stockage public..."
php artisan storage:link || echo "AVERTISSEMENT: La création du lien de stockage a échoué. Les fichiers uploadés pourraient ne pas être accessibles."

# --- 2. Opérations de base de données ---

echo "4. Exécution des migrations de base de données..."
php artisan migrate --force
if [ $? -ne 0 ]; then
  echo "ERREUR: Les migrations de base de données ont échoué ! Ceci est critique. Arrêt du déploiement."
  exit 1
fi

# --- 3. Optimisation et Caching Laravel ---

echo "6. Nettoyage et reconstruction des caches Laravel (config, route, cache, view)..."
php artisan optimize:clear
if [ $? -ne 0 ]; then
  echo "ERREUR: Le nettoyage des caches Laravel a échoué ! Arrêt du déploiement."
  exit 1
fi

echo "7. Reconstruction de l'autochargement Composer..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev
if [ $? -ne 0 ]; then
  echo "ERREUR: La reconstruction de l'autochargement Composer a échoué ! Arrêt du déploiement."
  exit 1
fi

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

echo "9. Définition des permissions pour les répertoires storage et bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# --- 5. Démarrage du serveur web ---

echo "10. Démarrage du serveur Apache en premier plan..."
exec apache2-foreground

echo "--- Script de déploiement Jobela RDC terminé ---"
