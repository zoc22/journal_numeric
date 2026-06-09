<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Workflow
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les configurations spécifiques au module
    | de gestion du workflow éditorial et des relectures.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration des transitions
    |--------------------------------------------------------------------------
    |
    | Définit les règles de transition entre les statuts d'article.
    | Chaque transition spécifie le statut source, le statut cible,
    | le rôle requis et les conditions optionnelles.
    |
    */
    'transitions' => [
        [
            'from' => 'brouillon',
            'to' => 'soumis',
            'role' => 'journaliste',
            'conditions' => ['contenu_min_length' => 100, 'has_category' => true],
        ],
        [
            'from' => 'soumis',
            'to' => 'en_relecture',
            'role' => 'editeur_associe',
            'conditions' => ['min_reviewers' => 2],
        ],
        [
            'from' => 'en_relecture',
            'to' => 'correction_demandee',
            'role' => 'reviewer',
        ],
        [
            'from' => 'en_relecture',
            'to' => 'valide_reviewer',
            'role' => 'reviewer',
        ],
        [
            'from' => 'correction_demandee',
            'to' => 'soumis',
            'role' => 'journaliste',
        ],
        [
            'from' => 'valide_reviewer',
            'to' => 'valide_editeur',
            'role' => 'editeur_associe',
        ],
        [
            'from' => 'valide_editeur',
            'to' => 'valide_directeur',
            'role' => 'directeur_collection',
        ],
        [
            'from' => 'valide_directeur',
            'to' => 'valide_final',
            'role' => 'editeur_chef',
        ],
        [
            'from' => 'valide_final',
            'to' => 'publie',
            'role' => 'editeur_chef',
        ],
        [
            'from' => '*',
            'to' => 'rejete',
            'role' => 'reviewer',
        ],
        [
            'from' => '*',
            'to' => 'archive',
            'role' => 'editeur_chef',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des reviews
    |--------------------------------------------------------------------------
    |
    | Paramètres pour le système de relecture.
    |
    */
    'review' => [
        'min_reviewers_per_article' => env('WORKFLOW_MIN_REVIEWERS', 2),
        'max_reviewers_per_article' => env('WORKFLOW_MAX_REVIEWERS', 5),
        'review_deadline_days' => env('WORKFLOW_REVIEW_DEADLINE_DAYS', 14),
        'auto_reminder_days' => [3, 7, 10],
        'max_iterations' => env('WORKFLOW_MAX_ITERATIONS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des notifications
    |--------------------------------------------------------------------------
    |
    | Active/désactive les notifications automatiques.
    |
    */
    'notifications' => [
        'on_submit' => env('WORKFLOW_NOTIFY_ON_SUBMIT', true),
        'on_approve' => env('WORKFLOW_NOTIFY_ON_APPROVE', true),
        'on_reject' => env('WORKFLOW_NOTIFY_ON_REJECT', true),
        'on_correction' => env('WORKFLOW_NOTIFY_ON_CORRECTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration des logs
    |--------------------------------------------------------------------------
    |
    | Active/désactive la journalisation détaillée des transitions.
    |
    */
    'logging' => [
        'enabled' => env('WORKFLOW_LOGGING_ENABLED', true),
        'log_all_transitions' => env('WORKFLOW_LOG_ALL_TRANSITIONS', true),
    ],
];
