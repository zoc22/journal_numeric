<?php
// app-modules/Core/Config/permissions.php

return [
    /**
     * Liste des permissions globales.
     * Elles seront chargées au démarrage.
     */
    'permissions' => [
        // Permissions système (utilisées par le module Core)
        'core.access.config',
        'core.manage.modules',

        // Permissions de base pour les rôles (seront affinées dans le module User)
        'user.view',
        'user.create',
        'user.edit',
        'user.delete',
    ],

    /**
     * Mapping entre les rôles et leurs permissions (valeurs par défaut).
     * Cela permet de préremplir la base lors du seeding.
     */
    'default_role_permissions' => [
        'super_admin' => ['*'], // toutes les permissions
        'admin_plateforme' => ['core.access.config', 'core.manage.modules', 'user.view', 'user.create', 'user.edit'],
        'editeur_chef' => ['user.view'],
        'lecteur' => [],
    ],
];
