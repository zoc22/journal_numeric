<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Article
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les configurations spécifiques au module
    | de gestion des articles.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Paramètres de pagination
    |--------------------------------------------------------------------------
    |
    | Définit le nombre d'éléments par page par défaut pour les listes d'articles.
    |
    */
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Versioning des articles
    |--------------------------------------------------------------------------
    |
    | Configuration du système de versioning :
    | - enabled: Active ou désactive le versioning automatique
    | - max_versions: Nombre maximum de versions conservées par article
    | - keep_days: Nombre de jours de conservation des versions
    |
    */
    'versioning' => [
        'enabled' => env('ARTICLE_VERSIONING_ENABLED', true),
        'max_versions' => env('ARTICLE_MAX_VERSIONS', 50),
        'keep_days' => env('ARTICLE_VERSION_KEEP_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Temps de lecture
    |--------------------------------------------------------------------------
    |
    | Paramètres pour le calcul automatique du temps de lecture.
    | vitesse_lecture_mots_par_minute: Vitesse de lecture moyenne en mots/minute.
    |
    */
    'lecture' => [
        'vitesse_lecture_mots_par_minute' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Slug génération
    |--------------------------------------------------------------------------
    |
    | Configuration pour la génération automatique des slugs SEO-friendly.
    |
    */
    'slug' => [
        'max_length' => 200,
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Images et médias
    |--------------------------------------------------------------------------
    |
    | Dimensions recommandées pour les images d'articles.
    |
    */
    'images' => [
        'principale' => [
            'width' => 1200,
            'height' => 630,
            'format' => 'webp',
            'quality' => 85,
        ],
        'thumbnail' => [
            'width' => 400,
            'height' => 225,
            'format' => 'webp',
            'quality' => 80,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow et statuts
    |--------------------------------------------------------------------------
    |
    | Configuration des statuts d'article et des transitions autorisées.
    |
    */
    'workflow' => [
        'statuts_initiaux' => ['brouillon', 'soumis'],
        'statuts_terminaux' => ['publie', 'rejete', 'archive'],
        'validation_requise' => true,
        'niveaux_validation' => [
            'reviewer' => 1,
            'editeur_associe' => 2,
            'directeur_collection' => 3,
            'editeur_chef' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configuration du cache pour les articles fréquemment consultés.
    |
    */
    'cache' => [
        'enabled' => env('ARTICLE_CACHE_ENABLED', true),
        'ttl_seconds' => env('ARTICLE_CACHE_TTL', 3600),
        'tags' => ['articles'],
    ],
];
