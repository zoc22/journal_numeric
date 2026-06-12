# Guide d'Installation

Suivez ces étapes pour installer et configurer le backend du Journal Numérique sur votre serveur.

## 1. Cloner le dépôt
```bash
git clone https://github.com/votre-organisation/backend-journal-numerique.git
cd backend-journal-numerique
```

## 2. Installer les dépendances
```bash
composer install
npm install
```

## 3. Configuration de l'environnement
Copiez le fichier d'exemple et générez la clé d'application.
```bash
cp .env.example .env
php artisan key:generate
```

Éditez le fichier `.env` pour configurer vos accès à la base de données.

## 4. Initialisation de la base de données
Exécutez les migrations de la base centrale et créez le premier administrateur.
```bash
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
```

## 5. Configuration du stockage
Créez le lien symbolique pour le stockage public.
```bash
php artisan storage:link
```

## 6. Build des assets (si nécessaire)
```bash
npm run build
```

## 7. Démarrage (Développement)
```bash
php artisan serve
```
Le serveur sera accessible sur `http://localhost:8000`.
