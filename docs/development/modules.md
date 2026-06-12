# Architecture Modulaire

La plateforme Journal Numérique est conçue selon une architecture modulaire. Chaque fonctionnalité métier est isolée dans son propre module sous le répertoire `app-modules/`.

## Structure d'un Module

Chaque module suit une structure standard inspirée de Laravel, permettant une isolation et une réutilisabilité maximales :

- `Config/` : Fichiers de configuration spécifiques au module.
- `database/` : Migrations, seeders et factories propres au module.
- `Http/` : Contrôleurs, Middlewares, Requests et Resources.
- `Models/` : Modèles Eloquent.
- `Providers/` : Service Providers pour enregistrer les services, routes et ressources du module.
- `resources/` : Vues et fichiers de traduction.
- `routes/` : Définition des routes (souvent `api.php`).
- `Services/` : Logique métier extraite des contrôleurs.
- `tests/` : Tests unitaires et fonctionnels spécifiques au module.

## Liste des Modules

### 1. Core
Module central fournissant les classes de base, les traits communs (ex: `HasUuid`, `HasAuditLog`) et les contrats. Il gère également le `ModuleManager`.

### 2. User
Gère les comptes utilisateurs, les profils et l'intégration avec le système de permissions.

### 3. Security
Fournit les services d'audit (AuditLog), la gestion des sessions, la limitation de taux (Rate Limiting) et la sécurité globale de l'API.

### 4. Article
Le cœur du système de gestion de contenu. Gère les articles, les catégories, les tags et le versionnage des contenus.

### 5. Workflow
Gère le processus éditorial complexe :
- Transitions de statut (Brouillon -> Soumis -> En Relecture -> ... -> Publié).
- Validation à plusieurs niveaux (Reviewer, Éditeur, Directeur).
- Assignation des relecteurs.

### 6. Media
Gestion centralisée des fichiers médias (images, documents). Supporte l'attachement polymorphique aux articles ou autres entités.

### 7. Maison (Tenancy)
Gère le concept de "Maison d'Édition" (Tenant). Chaque maison possède ses propres données, utilisateurs et configurations tout en partageant la même base de code.

### 8. Recruitment
Gère les appels à candidatures pour recruter de nouveaux contributeurs (journalistes, reviewers).

## Avantages de cette Approche
- **Isolation** : Les modifications dans un module ont un impact limité sur les autres.
- **Maintenabilité** : Code mieux organisé et plus facile à localiser.
- **Évolutivité** : Possibilité d'ajouter de nouvelles fonctionnalités simplement en créant un nouveau module.
