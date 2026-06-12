# API des Articles

Gère le contenu éditorial, les catégories et le versionnage.

## Endpoints des Articles (Tenant)

Ces routes sont relatives à la maison d'édition courante.

### Lister les articles
`GET /api/articles`

**Filtres disponibles :**
- `statut` : `brouillon`, `soumis`, `en_relecture`, `valide_reviewer`, `publie`, etc.
- `categorie_id` : Filtrer par identifiant de catégorie.
- `auteur_id` : Filtrer par identifiant d'auteur.
- `search` : Recherche textuelle dans le titre et le contenu.
- `continent` / `pays` / `ville` : Filtres de localisation.

### Détails d'un article
`GET /api/articles/{article}`

### Créer un article (Auth requis)
`POST /api/articles`

**Corps de la requête :**
- `titre` (string, requis)
- `contenu` (string, requis)
- `resume` (string)
- `categories` (array) - Liste des IDs de catégories.
- `meta_title`, `meta_description`, `meta_keywords` (string)

### Modifier un article (Auth requis)
`PUT /api/articles/{article}`
Un article n'est modifiable que s'il est en statut `brouillon` ou `correction_demandee`.

### Supprimer un article (Auth requis)
`DELETE /api/articles/{article}`
Seuls les brouillons ou articles rejetés peuvent être supprimés.

### Soumettre pour révision
`POST /api/articles/{article}/submit`
Pousse l'article dans le workflow éditorial.

## Gestion des Versions

### Lister les versions d'un article
`GET /api/articles/{article}/versions`

### Restaurer une version antérieure
`POST /api/articles/{article}/versions/{versionId}/restore`

## Endpoints des Catégories

### Lister les catégories
`GET /api/categories`

### Voir l'arborescence complète
`GET /api/categories/arborescence`

### Créer une catégorie (Admin requis)
`POST /api/categories`

**Corps de la requête :**
- `nom` (string, requis)
- `parent_id` (uuid, optionnel)
- `description` (string)
- `ordre` (int)
