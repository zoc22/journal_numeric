<?php
// app-modules/User/Config/config.php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module User
    |--------------------------------------------------------------------------
    */

    // Modèle User par défaut (peut être remplacé par extension)
    'user_model' => \Modules\User\Models\User::class,

    // Tables utilisateur (préfixe pour multi-tenant)
    'table_prefix' => '',

    // Rôles par défaut à créer lors du seeding
    'default_roles' => [
        'super_admin',
        'admin_plateforme',
        'editeur_chef',
        'directeur_collection',
        'editeur_associe',
        'reviewer',
        'journaliste',
        'lecteur',
    ],

    // Permissions par défaut
    'default_permissions' => [
        // Articles
        'article.create', 'article.edit', 'article.submit', 'article.delete',
        'article.approve_reviewer', 'article.approve_editor',
        'article.approve_director', 'article.publish',

        // Review
        'review.assign', 'review.feedback', 'review.validate', 'review.reject',

        // Maison d'édition
        'house.create', 'house.validate', 'house.manage_members', 'house.suspend',

        // Recrutement
        'call.create', 'call.manage', 'application.review',

        // Administration
        'user.manage', 'user.role.assign', 'user.view', 'audit.view',
    ],

    // Mapping rôles → permissions
    'role_permissions' => [
        'super_admin' => ['*'], // toutes les permissions

        'admin_plateforme' => [
            'house.validate', 'house.manage_members', 'house.suspend',
            'user.manage', 'user.role.assign', 'user.view', 'audit.view',
        ],

        'editeur_chef' => [
            'article.approve_director', 'article.publish',
            'call.create', 'call.manage', 'application.review',
            'house.manage_members', 'user.view',
        ],

        'directeur_collection' => [
            'article.approve_editor',
        ],

        'editeur_associe' => [
            'article.approve_reviewer',
        ],

        'reviewer' => [
            'review.assign', 'review.feedback', 'review.validate', 'review.reject',
        ],

        'journaliste' => [
            'article.create', 'article.edit', 'article.submit', 'article.delete',
        ],

        'lecteur' => [
            // aucune permission particulière (lecture publique)
        ],
    ],
];
