# Configuration du Système

Le système utilise les variables d'environnement (.env) pour sa configuration.

## Variables Principales

### Application
- `APP_NAME` : Nom de la plateforme.
- `APP_URL` : URL de base (ex: http://journal-central.com).
- `APP_ENV` : local, testing, ou production.

### Base de Données Centrale
- `DB_CONNECTION` : pgsql (recommandé) ou mysql.
- `DB_HOST` : Hôte de la BD.
- `DB_PORT` : 5432 (Postgres) ou 3306 (MySQL).
- `DB_DATABASE` : Nom de la base centrale.

### Multi-Tenancy (Stancl Tenancy)
Le système gère automatiquement les bases de données des tenants.
- `TENANT_DB_PREFIX` : Préfixe pour les bases de données tenants (ex: `tenant_`).

### Stockage (File Storage)
- `FILESYSTEM_DISK` : public ou s3.
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` (si utilisation de S3).

### Mail
Configurez vos accès SMTP pour l'envoi de notifications et la récupération de mot de passe.
- `MAIL_MAILER` : smtp.
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`

### Services OAuth (Google)
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI` : ${APP_URL}/api/auth/google/callback

## Tests avec Postman

Pour tester l'API multi-tenant avec Postman, vous devez prendre en compte l'identification par domaine.

### 1. Routes Centrales
Utilisez l'URL directe (ex: `http://127.0.0.1:8000/api/tenants`). Aucun header particulier n'est requis.

### 2. Routes Tenants (Maison d'édition)
Pour accéder aux routes d'une maison (ex: `api/articles`), vous devez simuler le domaine de la maison.
- **URL** : `http://localhost:8000/api/articles`
- **Header** : `Host` = `nom-maison.localhost` (ex: `maison1.localhost`)

Cela permet au middleware `InitializeTenancyByDomain` d'identifier la maison d'édition et de charger la base de données correspondante.

