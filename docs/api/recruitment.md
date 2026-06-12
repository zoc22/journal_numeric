# API de Recrutement

Gère les appels à candidatures et les soumissions des journalistes.

## Appels à Candidatures

### Lister les appels publics
`GET /api/recruitment/calls`

### Créer un appel (Éditeur)
`POST /api/recruitment/calls`

**Paramètres :**
- `titre`
- `description`
- `date_limite`
- `nombre_postes`

### Publier un appel
`POST /api/recruitment/calls/{call}/publish`

### Clôturer un appel
`POST /api/recruitment/calls/{call}/close`

## Candidatures

### Soumettre une candidature
`POST /api/recruitment/applications/call/{call}`

**Paramètres :**
- `cv_url`
- `lettre_motivation`
- `portfolio_links` (array)

### Lister mes candidatures
`GET /api/recruitment/applications`

### Évaluer une candidature (Admin)
`POST /api/recruitment/applications/{application}/review`

**Paramètres :**
- `status` (accepted, rejected, pending_interview)
- `comment`
