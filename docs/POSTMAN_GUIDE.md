# Guide de Tests Postman - Journal Numérique API

Ce guide répertorie toutes les routes de l'API, les corps de requête nécessaires et la procédure pour tester chaque fonctionnalité.

## Configuration Postman

### 1. Variables d'Environnement
Créez un environnement Postman avec les variables suivantes :
- `base_url`: `http://localhost:8000`
- `token`: (laissé vide, sera rempli après le login)
- `admin_token`: (pour les actions super-admin)
- `tenant_host`: `maison1.test` (ou tout autre domaine de tenant créé)

### 2. Header Multi-Tenant
Pour TOUTES les routes dépendant d'une maison d'édition (Tenant), vous **devez** ajouter le header suivant :
- **Key**: `Host`
- **Value**: `{{tenant_host}}`

---

## 1. Administration Centrale (Sans Tenant)

Ces routes servent à gérer la plateforme globale. Utilisez `127.0.0.1:8000` ou `localhost:8000` sans header `Host` spécifique (ou avec le domaine central).

### Créer un Tenant (Maison d'édition)
- **Route**: `POST {{base_url}}/api/tenants`
- **Body** (JSON):
```json
{
    "name": "Maison Test 1",
    "domain": "maison1.test"
}
```
- **Résultat attendu**: `201 Created`
- **Procedure**: Créez d'abord le tenant pour pouvoir tester les routes suivantes.

---

## 2. Authentification (Avec Tenant)

**Important**: Ajoutez le header `Host: maison1.test`.

### Inscription
- **Route**: `POST {{base_url}}/api/register`
- **Body**:
```json
{
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
- **Résultat attendu**: `201 Created` + Token.

### Connexion
- **Route**: `POST {{base_url}}/api/login`
- **Body**:
```json
{
    "email": "jean.dupont@example.com",
    "password": "password123"
}
```
- **Résultat attendu**: `200 OK`. Copiez le `token` dans votre variable d'environnement Postman.

### Profil (Me)
- **Route**: `GET {{base_url}}/api/me`
- **Auth**: Bearer Token `{{token}}`
- **Résultat attendu**: `200 OK` avec les infos de l'utilisateur.

---

## 3. Gestion des Articles (Module Article)

### Créer un Article
- **Route**: `POST {{base_url}}/api/articles`
- **Auth**: Bearer Token
- **Header**: `Host: maison1.test`
- **Body**:
```json
{
    "titre": "Mon premier article",
    "contenu": "Ceci est le contenu détaillé de mon premier article sur la plateforme.",
    "categories": []
}
```
- **Résultat attendu**: `201 Created`.

### Lister les Articles
- **Route**: `GET {{base_url}}/api/articles`
- **Filtres (Params)**: `statut=brouillon`, `search=premier`
- **Résultat attendu**: `200 OK` avec pagination.

### Voir un Article
- **Route**: `GET {{base_url}}/api/articles/{uuid}`
- **Résultat attendu**: `200 OK` ou `404` si l'ID n'appartient pas au tenant actuel.

---

## 4. Workflow Éditorial (Module Workflow)

### Soumettre un article
- **Route**: `POST {{base_url}}/api/articles/{uuid}/submit`
- **Résultat attendu**: `200 OK`, le statut passe à `soumis`.

### Valider (Niveau Reviewer)
- **Route**: `POST {{base_url}}/api/workflow/validate/reviewer/{uuid}`
- **Note**: Nécessite le rôle/permission adéquat.
- **Résultat attendu**: `200 OK`.

---

## 5. Médias (Module Media)

### Upload d'image
- **Route**: `POST {{base_url}}/api/workflow/media`
- **Body**: `form-data`
    - `file`: (choisir un fichier image)
    - `titre`: "Mon Image"
- **Résultat attendu**: `201 Created`.

---

## 6. Recrutement (Module Recruitment)

### Créer un appel à candidatures
- **Route**: `POST {{base_url}}/api/recruitment/calls`
- **Body**:
```json
{
    "titre": "Recherche Journaliste Sportif",
    "description": "Nous cherchons un expert en foot.",
    "date_limite": "2026-12-31"
}
```
- **Résultat attendu**: `201 Created`.

---

## 7. Sécurité et Audit

### Historique de connexion
- **Route**: `GET {{base_url}}/security/login-history`
- **Résultat attendu**: `200 OK` avec la liste des IPs et dates.

### Logs d'audit
- **Route**: `GET {{base_url}}/audit/logs`
- **Résultat attendu**: `200 OK` montrant les actions effectuées (created article, login, etc.).

---

## Procédure de test complète (Workflow Standard)

1. **Setup**: Créez un tenant `maison1.test` via `POST /api/tenants`.
2. **Auth**: Inscrivez-vous via `POST /api/register` (avec Host: maison1.test).
3. **Maison**: Vérifiez les infos de votre maison via `GET /api/maison/gerer`.
4. **Contenu**:
    - Créez un article en brouillon.
    - Téléversez une image.
    - Attachez l'image à l'article.
    - Soumettez l'article au workflow.
5. **Validation**: Connectez-vous avec un compte ayant les droits de Reviewer et validez l'article.
6. **Public**: Vérifiez que l'article apparaît dans la liste publique `GET /api/articles` (sans token).
