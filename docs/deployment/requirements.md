# Prérequis du Système

Pour faire fonctionner le backend du Journal Numérique, votre serveur doit répondre aux exigences suivantes.

## Serveur Web & Environnement
- **PHP** : >= 8.2
- **Serveur Web** : Nginx, Apache ou Caddy
- **Système d'exploitation** : Linux (Ubuntu recommandé), macOS ou Windows (WAMP/Laragon pour développement)

## Base de données
- **PostgreSQL** : >= 13 (Recommandé pour la production)
- **MySQL** : >= 8.0 (Supporté)
- **SQLite** : Uniquement pour le développement local et les tests.

## Dépendances PHP requises
Les extensions PHP suivantes doivent être installées et activées :
- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `filter`
- `gd` ou `imagick` (pour la gestion des images via Intervention Image)
- `hash`
- `mbstring`
- `openssl`
- `pcre`
- `pdo` (avec driver pgsql ou mysql)
- `session`
- `tokenizer`
- `xml`

## Outils de build et gestionnaires
- **Composer** : >= 2.x
- **Node.js** : >= 18.x
- **NPM** : >= 9.x
