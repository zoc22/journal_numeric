# API d'Administration Plateforme

Gestion globale de la plateforme, des maisons d'édition et de la configuration centrale.

## Gestion des Maisons d'Édition (Tenants)

### Lister toutes les maisons
`GET /api/maison/admin/maisons`

### Lister les maisons en attente de validation
`GET /api/maison/admin/maisons/en-attente`

### Créer une nouvelle maison
`POST /api/maison/admin/maisons`

**Paramètres :**
- `nom`
- `email_contact`
- `description`

### Valider/Activer une maison
`POST /api/maison/admin/maisons/{maisonId}/valider`

### Rejeter une maison
`POST /api/maison/admin/maisons/{maisonId}/rejeter`

### Suspendre une maison
`POST /api/maison/admin/maisons/{maisonId}/suspendre`

### Supprimer une maison (Super Admin)
`DELETE /api/maison/admin/maisons/{maisonId}`

## Statistiques Globales

### Tableau de bord admin
`GET /api/maison/admin/stats`

## Synchronisation

### Synchroniser les permissions entre tenants
`POST /api/maison/admin/sync-permissions`
