#!/bin/bash
set -e

echo "🚀 Démarrage de l'application..."

# Créer le fichier de base de données SQLite s'il n'existe pas
if [ ! -f database/database.sqlite ]; then
    echo "📁 Création du fichier SQLite..."
    touch database/database.sqlite
    chmod 664 database/database.sqlite
fi

# Si on utilise MySQL, attendre qu'il soit prêt
if [ "${DB_CONNECTION}" = "mysql" ]; then
    echo "⏳ Attente de MySQL..."
    until php artisan db:show > /dev/null 2>&1; do
        echo "⏳ Base de données MySQL non disponible, nouvelle tentative dans 2 secondes..."
        sleep 2
    done
    echo "✅ MySQL disponible!"
else
    echo "✅ Utilisation de SQLite"
fi

# Vérifier si le fichier .env existe
if [ ! -f .env ]; then
    echo "⚠️  Fichier .env introuvable, copie de .env.example..."
    cp .env.example .env
fi

# Générer la clé d'application si elle n'existe pas
if grep -q "APP_KEY=$" .env; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Créer le lien symbolique pour le storage
if [ ! -L public/storage ]; then
    echo "🔗 Création du lien symbolique storage..."
    php artisan storage:link
fi

# Exécuter les migrations
echo "📊 Exécution des migrations..."
php artisan migrate --force

# Nettoyer et optimiser le cache
echo "🧹 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "⚡ Optimisation de l'application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optionnel: Seeder pour les données de test/initiales
# echo "🌱 Exécution des seeders..."
# php artisan db:seed --force

echo "✅ Application prête!"

# Exécuter la commande passée en argument (apache2-foreground par défaut)
exec "$@"
