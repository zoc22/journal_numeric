# API Media

Gestion des fichiers multimédias et association avec le contenu éditorial.

## Gestion des Médias (Auth + Tenant)

### Lister les médias de la maison
`GET /api/workflow/media`

### Téléverser un média
`POST /api/workflow/media`

**Paramètres (multipart/form-data) :**
- `file` (file, requis) - Image, PDF, etc.
- `titre` (string)
- `description` (string)
- `alt_text` (string)

### Modifier les métadonnées
`PUT /api/workflow/media/{medium}`

### Supprimer un média
`DELETE /api/workflow/media/{medium}`

## Association avec les Articles

### Lister les médias d'un article
`GET /api/workflow/article-media/{article}`

### Attacher un média à un article
`POST /api/workflow/article-media/{article}/{medium}`

### Détacher un média
`DELETE /api/workflow/article-media/{article}/{medium}`

### Définir l'image de couverture
`POST /api/workflow/article-media/{article}/{medium}/cover`

### Ordonner les médias d'un article
`PUT /api/workflow/article-media/{article}/order`

**Paramètres :**
- `order` (array) - Liste des IDs de médias dans l'ordre souhaité.
