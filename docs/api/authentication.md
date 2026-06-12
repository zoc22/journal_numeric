# API d'Authentification

Gère l'accès à la plateforme, l'inscription et la récupération de compte.

## Endpoints Publics

### Connexion
`POST /api/login`

**Paramètres :**
- `email` (string, requis)
- `password` (string, requis)
- `device_name` (string, optionnel) - Nom de l'appareil pour le token Sanctum.

**Réponse (200 OK) :**
```json
{
    "success": true,
    "token": "1|abc...",
    "user": {
        "id": "...",
        "nom": "...",
        "email": "..."
    }
}
```

### Inscription
`POST /api/register`

**Paramètres :**
- `nom` (string, requis)
- `prenom` (string, requis)
- `email` (string, requis, unique)
- `password` (string, requis, min:8, confirmé)
- `continent` (string)
- `pays` (string)
- `ville` (string)

### Mot de passe oublié
`POST /api/password/forgot`

**Paramètres :**
- `email` (string, requis)

### Réinitialisation du mot de passe
`POST /api/password/reset`

**Paramètres :**
- `token` (string, requis)
- `email` (string, requis)
- `password` (string, requis, min:8, confirmé)

## Authentification Google (OAuth2)

### Redirection vers Google
`GET /api/auth/google`

### Callback Google
`GET /api/auth/google/callback`

## Endpoints Protégés (auth:sanctum)

### Déconnexion
`POST /api/logout`

### Profil actuel
`GET /api/me`

### Mettre à jour le profil
`PUT /api/profile`
