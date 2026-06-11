<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Recruitment
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les configurations spécifiques à la gestion
    | des appels à candidatures et des candidatures.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Paramètres des appels à candidatures
    |--------------------------------------------------------------------------
    |
    */
    'calls' => [
        'default_duration_days' => env('RECRUITMENT_CALL_DEFAULT_DURATION', 30),
        'max_duration_days' => env('RECRUITMENT_CALL_MAX_DURATION', 90),
        'auto_close_expired' => env('RECRUITMENT_AUTO_CLOSE_EXPIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paramètres des candidatures
    |--------------------------------------------------------------------------
    |
    */
    'applications' => [
        'max_per_candidate_per_call' => 1,
        'max_file_size' => 10 * 1024 * 1024, // 10 MB
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    */
    'notifications' => [
        'on_call_published' => env('RECRUITMENT_NOTIFY_ON_CALL_PUBLISHED', true),
        'on_application_submitted' => env('RECRUITMENT_NOTIFY_ON_APPLICATION_SUBMITTED', true),
        'on_application_reviewed' => env('RECRUITMENT_NOTIFY_ON_APPLICATION_REVIEWED', true),
    ],
];
