# Dockerfile multi-stage pour application Laravel avec Vue.js/Inertia

# Stage 1: Build des assets frontend
FROM php:8.4-cli AS frontend-builder

WORKDIR /app

# Installer Node.js dans l'image PHP
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./
COPY package.json package-lock.json* ./

# Installer les dépendances PHP (nécessaire pour Wayfinder)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Installer les dépendances Node
RUN npm ci --prefer-offline --no-audit

# Copier le code source nécessaire pour le build
COPY . .

# Build des assets (Wayfinder peut maintenant s'exécuter)
RUN npm run build

# Stage 2: Image finale PHP
FROM php:8.4-apache

# Métadonnées
LABEL maintainer="Votre Nom <votre@email.com>"
LABEL description="Application de réservation de salles - Laravel + Vue.js"

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurer Apache
RUN a2enmod rewrite headers
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP (sans les dev dependencies en production)
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader --prefer-dist

# Copier le reste de l'application
COPY . .

# Copier les assets buildés depuis le stage frontend
COPY --from=frontend-builder /app/public/build ./public/build

# Générer l'autoloader optimisé
RUN composer dump-autoload --optimize

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Script d'entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exposer le port 80
EXPOSE 80

# Utiliser l'entrypoint personnalisé
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Commande par défaut
CMD ["apache2-foreground"]
