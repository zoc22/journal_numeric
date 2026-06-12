# API de Sécurité et d'Audit

Gère les journaux d'audit, l'historique des connexions et la sécurité des comptes.

## Journaux d'Audit (Tenant)

Accessible par les administrateurs de la maison d'édition.

### Lister les logs
`GET /audit/logs`

**Filtres :**
- `user_id`
- `action` (created, updated, deleted, login, etc.)
- `date_from` / `date_to`

### Détail d'un log
`GET /audit/logs/{auditLog}`

### Statistiques d'audit
`GET /audit/statistiques`

## Sécurité de l'utilisateur

### Historique des connexions
`GET /security/login-history`

### Sessions actives
`GET /security/active-sessions`

### Terminer les autres sessions
`POST /security/terminate-other-sessions`

### Terminer une session spécifique
`DELETE /security/session/{sessionId}`

## Administration Globale (Central)

Réservé au Super Admin de la plateforme.

### Tous les logs système
`GET /admin/audit/logs`

### Logs critiques
`GET /admin/audit/critical`

### Nettoyage des logs anciens
`DELETE /admin/audit/clean`
