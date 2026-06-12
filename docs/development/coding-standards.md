# Standards de Codage

Afin de garantir la cohérence et la qualité du code, tous les contributeurs doivent respecter les standards suivants.

## Langage et Typage
- **PHP 8.2+** : Utilisation des dernières fonctionnalités (Readonly properties, Enums, Intersection types).
- **Strict Typing** : Chaque fichier PHP doit commencer par `declare(strict_types=1);`.
- **Typage fort** : Toutes les méthodes doivent avoir des types de retour et des types d'arguments définis.

## Conventions de Nommage
- **Classes** : PascalCase (ex: `ArticleService`).
- **Méthodes et Variables** : camelCase (ex: `publierArticle`).
- **Colonnes de BD** : snake_case (ex: `maison_id`).
- **Fichiers** : PascalCase pour les classes, snake_case pour les configurations et migrations.

## Organisation du Code
- **Contrôleurs** : Doivent rester "fins" (Thin Controllers). Toute logique complexe doit être déplacée dans un Service.
- **Modèles** : Utilisation des UUIDs pour les clés primaires. Définir explicitement les `$fillable` et `$casts`.
- **Validation** : Utilisation systématique des `FormRequest` pour valider les données entrantes.
- **Ressources** : Utilisation des `JsonResource` pour transformer les modèles en réponses JSON.

## Tests
- **Couverture** : Chaque nouvelle fonctionnalité doit être accompagnée de tests unitaires (Services) et fonctionnels (Contrôleurs).
- **Structure** : Suivre la structure modulaire pour les tests (`tests/Feature/Modules/...`).
- **Données** : Utilisation des Factories pour générer les données de test.

## Documentation
- **PHPDoc** : Chaque classe et méthode publique doit être documentée avec des blocs PHPDoc expliquant le rôle et les paramètres.
- **API** : Les modifications de routes doivent être reflétées dans la documentation API correspondante.
