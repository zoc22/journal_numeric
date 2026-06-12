# Architecture Technique

## Vue d'ensemble
Le backend du Journal Numérique est une API REST développée avec Laravel 12. Elle utilise une architecture multi-tenant basée sur `stancl/tenancy` pour isoler les données des différentes maisons d'édition.

## Principes de Conception

### 1. Multi-Tenancy
La plateforme supporte deux modes d'isolation :
- **Base de données séparées (Production)** : Chaque maison d'édition possède sa propre base de données physique, garantissant une isolation totale.
- **Scoping par colonne (Fallback)** : Pour certains modèles ou environnements de test, une colonne `maison_id` est utilisée pour filtrer les données.

### 2. Design Pattern Service-Service
La logique métier est extraite dans des classes **Services** (ex: `ArticleService`, `WorkflowService`). Ces services sont injectés dans les contrôleurs et peuvent interagir entre eux.

### 3. Modularité
Le projet est divisé en modules autonomes situés dans `app-modules/`. Chaque module gère son propre domaine (User, Article, Media, etc.) avec ses propres routes, migrations et modèles.

### 4. Sécurité et Audit
- **Sanctum** : Authentification via jetons API.
- **Spatie Permission** : Gestion fine des rôles et permissions, synchronisée entre les tenants.
- **Audit Log** : Chaque action sur un modèle utilisant le trait `HasAuditLog` est enregistrée.

### 5. Workflow Éditorial
Un système complexe de transitions d'états gère le cycle de vie des articles, avec des validations multi-niveaux (Reviewer -> Éditeur Associé -> Directeur -> Éditeur en Chef).

## Stack Technique
- **Framework** : Laravel 12
- **Multi-tenancy** : Stancl Tenancy v3
- **Base de données** : PostgreSQL (Production), SQLite (Tests)
- **Gestion de fichiers** : Local / S3 (via Intervention Image)
- **Authentification** : Laravel Sanctum & Socialite (Google)
