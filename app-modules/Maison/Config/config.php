<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Nom du module
    |--------------------------------------------------------------------------
    */
    'name' => 'Maison',

    /*
    |--------------------------------------------------------------------------
    | Version du module
    |--------------------------------------------------------------------------
    */
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Activation du module
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Statuts possibles pour une maison d'édition
    |--------------------------------------------------------------------------
    */
    'statuts' => [
        'en_attente' => 'En attente de validation',
        'active' => 'Active',
        'suspendue' => 'Suspendue',
        'rejetee' => 'Rejetée',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rôles par défaut pour une nouvelle maison
    |--------------------------------------------------------------------------
    */
    'default_roles' => [
        'editeur_en_chef' => 'Éditeur en Chef',
        'directeur_collection' => 'Directeur de Collection',
        'editeur_associe' => 'Éditeur Associé',
        'reviewer' => 'Reviewer',
        'journaliste' => 'Journaliste',
        'lecteur' => 'Lecteur',
    ],
];
