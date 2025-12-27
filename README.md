# 🏢 Système de Réservation de Salles

Application web de gestion des réservations de salles de réunion développée avec Laravel 12 et Vue 3. Test technique. 21/12/2025

## Lancement

### Prérequis

- PHP 8.4 ou supérieur
- Composer
- Node.js 18+ et npm
- Extension PHP SQLite (généralement incluse par défaut)

### Installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/NOaMTL/test-technique.git
   cd test-technique
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install
   ```

4. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate --ansi
   ```

5. **Configurer la base de données**
   
   L'application utilise SQLite par défaut. Éditez le fichier `.env` :
   ```env
   DB_CONNECTION=sqlite
   # DB_DATABASE sera automatiquement database/database.sqlite
   ```
   
   Le fichier `database/database.sqlite` sera créé automatiquement lors de l'installation des dépendances (POST-INSTALL SCRIPT).

6. **Créer le lien symbolique pour le storage**
   ```bash
   php artisan storage:link
   ```
   Cette commande crée un lien symbolique permettant d'accéder aux fichiers uploadés (images des salles).

7. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

8. **Seeder (recommandé)**
   ```bash
   php artisan db:seed
   ```
   Cela créera des utilisateurs, salles avec images, réservations de test pour démarrer rapidement.

9. **Compiler les assets**
   ```bash
   npm run build
   ```
   Cette étape est nécessaire pour que les tests puissent s'exécuter correctement.

10. **Vérifier l'installation avec les tests**
   ```bash
   php artisan test
   ```
   Cette commande lance la suite de tests complète pour vérifier que l'application fonctionne correctement. Vous devriez voir : ✅ 74 tests passants (185 assertions).

### Lancement de l'application

#### Option 1 : Lancement local (développement)

1. **Démarrer le serveur Laravel**
   ```bash
   php artisan serve
   ```
   L'application sera accessible sur `http://localhost:8000`

2. **Compiler les assets (développement)**
   
   Dans un terminal séparé :
   ```bash
   npm run dev
   ```

3. **Build pour production**
   ```bash
   npm run build
   ```

#### Option 2 : Lancement avec Docker 🐳

**Démarrage rapide :**

1. **Lancer l'application** (construction + démarrage automatique)
   ```bash
   docker-compose -f docker-compose.test.yml up --build
   ```

2. **Accéder à l'application**
   
   Ouvrez le navigateur sur `http://localhost:8000`

3. **Arrêter l'application**
   ```bash
   docker-compose -f docker-compose.test.yml down
   ```

### Comptes par défaut

Après avoir exécuté les seeders, vous pouvez utiliser ces comptes :
- **Admin** : admin@example.com / password
- **Utilisateur** : user@example.com / password

### Technologies utilisées

**Backend :**
- Laravel 12
- Fortify (authentification)
- Inertia.js (pont Laravel-Vue)
- SQLite

**Frontend :**
- Vue 3 (Composition API)
- TypeScript
- Tailwind CSS
- Vite (build tool)
- Lucide Icons
- Reka-ui


## 📋 Roadmap des améliorations

Ce document liste les améliorations envisagées pour faire évoluer l'application vers une solution encore plus complète et professionnelle.

---

## 🎨 Améliorations UI/UX

### Amélioration Responsive
- Optimisation de la mise en page pour les tablettes (layouts intermédiaires)
- Amélioration de la navigation mobile (menu hamburger, bottom navigation)
- Gestion des tableaux en mode mobile (scroll horizontal, cartes empilées)
- Adaptation des formulaires pour une meilleure saisie tactile
- Tests sur différents devices et breakpoints

### Amélioration de l'accessibilité
- Conformité WCAG 2.1 niveau AA
- Support complet de la navigation au clavier (focus visible, tab order logique)
- Ajout d'attributs ARIA appropriés sur tous les composants interactifs
- Support des lecteurs d'écran (labels appropriés, live regions pour notifications)
- Contraste de couleurs optimisé (ratios conformes)
- Mode haute lisibilité avec tailles de police ajustables
- Skip links pour navigation rapide
- Gestion des focus traps dans les modales et menus

### Vue Semaine pour le calendrier des réservations
- Implémentation d'un calendrier hebdomadaire interactif
- Affichage des réservations en grille horaire
- Navigation par semaine (précédent/suivant, sélection de date)
- Indicateurs visuels de disponibilité des salles
- Drag & drop pour créer/modifier des réservations
- Vue condensée mobile (liste avec filtres temporels)

### Utilisation plus uniforme de la charte graphique du CA
- Audit et standardisation de toutes les couleurs (primaire, secondaire, tertiaire)
- Application systématique des classes `bg-msl-primary`, `text-msl-primary`, etc.
- Harmonisation des espacements et paddings selon une grille cohérente
- Standardisation des ombres (shadow-msl-s, shadow-msl-m, shadow-msl-l)
- Unification des bordures et radius
- Design system documenté
- Guidelines de composants réutilisables

### Utilisation de SASS
- Migration de Tailwind CSS vers une approche hybride avec SASS
- Organisation modulaire des styles (variables, mixins, composants)
- Gestion des thèmes via variables SASS
- Nesting pour une meilleure lisibilité
- Utilisation de fonctions SASS pour calculs dynamiques
- Build optimisé avec purge CSS

### Gestion du thème sombre/clair
- Amélioration du contraste de la barre latérale en mode sombre
- Adaptation des images/icônes selon le thème
- Variables CSS dynamiques pour les couleurs de thème

---

## ⚙️ Améliorations Features

### Possibilité de créer une réservation depuis la sélection d'une salle
- Page de détail pour chaque salle avec calendrier de disponibilité
- Sélection directe d'un créneau disponible depuis la page salle
- Pré-remplissage automatique du formulaire (salle + créneau)
- Vue de tous les créneaux disponibles pour la journée/semaine
- Suggestion de créneaux alternatifs si le créneau souhaité est pris

### Gestion améliorée des salles (CRUD admin)
- Interface d'administration complète pour les salles
- Formulaire de création/modification avancé
- Upload et gestion de plusieurs photos par salle
- Gestion des équipements intégrés (liste de matériel fixe)
- **Système de règles de réservation par salle :**
  - Définition des jours/horaires autorisés (ex: lundi-vendredi 8h-18h)
  - Durée minimale/maximale de réservation
  - Délai de réservation à l'avance (min/max)
  - Restriction par rôle
  - Période de blocage pour maintenance
  - Validation obligatoire pour certaines salles (ou toutes)
- Historique des modifications
- Archivage de salles (soft delete)

### Gestion des équipements disponibles
- Entité `Equipment` séparée des salles
- Catégorisation des équipements (audiovisuel, informatique, mobilier, etc.)
- **Gestion des quantités :**
  - Stock total et disponible par équipement
  - Réservation d'équipements en complément d'une salle
  - Vérification de disponibilité selon la quantité demandée
  - Historique de réservation par équipement
- Traçabilité complète (qui a réservé quoi, quand)
- Système de maintenance préventive

### Gestion plus fine des permissions/rôles
- Système de rôles personnalisables (au-delà de admin/user)
- Permissions granulaires :
  - Réserver pour soi / Réserver pour d'autres
  - Modifier ses réservations / Modifier toutes les réservations
  - Voir toutes les réservations / Voir uniquement ses réservations
  - Gérer les salles / Gérer les utilisateurs / Gérer les paramètres
- Délégation de droits (assistant peut gérer pour son manager)
- Quotas de réservation par rôle
- Matrice de permissions documentée

### Validation des réservations automatique ou par admin
- Configuration globale : validation automatique ou manuelle
- Configuration par salle : certaines salles nécessitent validation
- Configuration par rôle : certains utilisateurs ont auto-validation
- Workflow de validation :
  - Statut "En attente de validation"
  - Notification aux validateurs
  - Acceptation/refus avec commentaire
  - Notification au demandeur
- Historique des validations
- Escalade automatique si pas de réponse sous X jours
- Validation groupée (plusieurs réservations en une fois)

### Logs/Audit
- Logs complets de toutes les actions utilisateurs
- Traçabilité des modifications (qui, quoi, quand, avant/après)
- Logs d'accès et de connexion
- Logs de validation/refus de réservations
- Interface d'administration pour consulter les logs
- Filtrage et recherche avancée dans les logs
- Export des logs pour analyse
- Archivage automatique des anciens logs
- Conformité RGPD (anonymisation possible)

### Upload d'images réelles pour les salles
- Upload multiple d'images par salle
- Gestion de galerie photo (ajout, suppression, réorganisation)
- Image principale + images secondaires
- Redimensionnement et optimisation automatiques
- Support de formats variés (JPEG, PNG, WebP)
- Prévisualisation avant upload
- Stockage optimisé (Laravel Storage avec S3 optionnel)
- Lazy loading des images pour performance
- Lightbox pour visualisation plein écran

### Envoi d'emails aux participants (idée)
- Email de confirmation de réservation
- Email de rappel J-1 ou X heures avant
- Email de modification de réservation
- Email d'annulation
- Email d'invitation aux participants ajoutés
- Templates d'emails personnalisables
- Préférences de notification par utilisateur (recevoir ou non)
- Support des pièces jointes (fichier ICS pour calendrier)
- Tracking des emails envoyés
- Mode digest : emails groupés quotidiens/hebdomadaires

### Mise en cache Redis pour performances
- Cache des données fréquemment consultées :
  - Liste des salles avec équipements
  - Disponibilités sur périodes courantes
  - Statistiques du dashboard
- Stratégie de cache intelligent avec invalidation sélective
- Cache de requêtes lourdes
- Session stockée en Redis
- Monitoring de hit rate du cache

### Queue system pour notifications asynchrones
- Utilisation de Laravel Queues (Redis ou database driver)
- Jobs pour envoi d'emails (non-bloquant)
- Jobs pour génération de rapports
- Jobs pour nettoyage de données
- Workers supervisés (Supervisor)
- Retry logic en cas d'échec
- Monitoring des queues et failed jobs
- Dashboard de visualisation des jobs (Horizon)

## 🧪 Tests

L'application dispose d'une suite de tests complète couvrant les fonctionnalités principales.

### Lancer les tests

```bash
# Lancer tous les tests
php artisan test

# Lancer uniquement les tests d'une fonctionnalité spécifique
php artisan test --filter=RoomAvailability

# Lancer les tests avec couverture de code
php artisan test --coverage

# Lancer uniquement les tests unitaires
php artisan test --testsuite=Unit

# Lancer uniquement les tests fonctionnels
php artisan test --testsuite=Feature
```

### Couverture actuelle

- ✅ 74 tests passants (185 assertions)
- ✅ Tests de disponibilité des salles (13 tests)
- ✅ Tests des contraintes de réservation (18 tests)
- ✅ Tests API de réservation
- ✅ Parcours utilisateur complet (E2E)

### CI/CD pipeline
- Pipeline GitLab CI
- Étapes :
  - Linting (PHP CS Fixer, ESLint)
  - Tests automatisés
  - Build des assets (Vite)
  - Analyse de code (PHPStan, Larastan)
  - Scan de sécurité des dépendances
- Déploiement automatique :
  - Preview environments pour pull requests
  - Staging automatique sur branche develop
  - Production sur tag/release avec validation manuelle
- Notifications Slack/Teams sur succès/échec
- Rollback automatique en cas d'échec

### Monitoring et alertes (Sentry, etc.)
- Intégration Sentry pour tracking d'erreurs
- Monitoring de performance (temps de réponse, requêtes lentes)
- Alertes en temps réel pour erreurs critiques
- Dashboard de métriques applicatives :
  - Nombre de réservations par jour/semaine/mois
  - Temps de réponse moyen
  - Taux d'erreur
  - Utilisation des ressources serveur
- Health checks automatiques
- Monitoring de la queue et des workers
- Logs centralisés
- Alerting configurable (email, Slack, ...)

---
