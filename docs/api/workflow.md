# API de Workflow Éditorial

Gère le cycle de vie des articles, les validations multi-niveaux et les reviews.

## Validation des Articles

Les articles passent par plusieurs niveaux de validation avant publication.

### Validation par Reviewer (Niveau 3)
`POST /api/workflow/validate/reviewer/{article}`

### Validation par Éditeur Associé (Niveau 4)
`POST /api/workflow/validate/editeur-associe/{article}`

### Validation par Directeur de Collection (Niveau 5)
`POST /api/workflow/validate/directeur/{article}`

### Validation Finale (Éditeur en Chef - Niveau 6)
`POST /api/workflow/validate/final/{article}`

### Publication
`POST /api/workflow/validate/publier/{article}`

## Assignation des Reviews

### Lister mes assignations
`GET /api/workflow/review-assignments/my`

### Assigner un reviewer à un article
`POST /api/workflow/review-assignments`

**Paramètres :**
- `article_id`
- `user_id`
- `deadline`

### Accepter/Refuser une assignation
`POST /api/workflow/review-assignments/{assignment}/accept`
`POST /api/workflow/review-assignments/{assignment}/reject`

## Historique et Transitions

### Historique d'un article
`GET /api/workflow/history/article/{article}`

### Transitions possibles
`GET /api/workflow/transition/{article}/possible`

### Forcer une transition
`POST /api/workflow/transition/{article}`
