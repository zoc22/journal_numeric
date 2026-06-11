<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration du module Security/Audit
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les configurations spécifiques à la sécurité
    | et à la journalisation des actions.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Journalisation des logs
    |--------------------------------------------------------------------------
    |
    */
    'logging' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'log_sensitive_actions' => env('AUDIT_LOG_SENSITIVE', true),
        'log_all_actions' => env('AUDIT_LOG_ALL', false),

        // Actions sensibles à logger obligatoirement
        'sensitive_actions' => [
            'login', 'logout', 'password_change', 'role_assignment',
            'permission_change', 'article_publish', 'article_delete',
            'user_delete', 'tenant_create', 'tenant_delete',
            'settings_change', 'user_impersonate', 'api_key_create',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'max_attempts' => [
            'login' => 5,
            'api' => 60,
            'sensitive' => 10,
        ],
        'decay_minutes' => [
            'login' => 15,
            'api' => 1,
            'sensitive' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sessions utilisateur
    |--------------------------------------------------------------------------
    |
    */
    'sessions' => [
        'track_enabled' => env('SESSION_TRACKING_ENABLED', true),
        'max_concurrent_sessions' => env('MAX_CONCURRENT_SESSIONS', 5),
        'session_timeout_minutes' => env('SESSION_TIMEOUT_MINUTES', 120),
    ],

    /*
    |--------------------------------------------------------------------------
   | Sécurité
    |--------------------------------------------------------------------------
    |
    */
    'security' => [
        'password_min_length' => 8,
        'require_strong_password' => env('REQUIRE_STRONG_PASSWORD', true),
        'max_login_attempts' => 5,
        'lockout_time_minutes' => 15,
        'two_factor_enabled' => env('TWO_FACTOR_ENABLED', false),
        'ip_whitelist_enabled' => env('IP_WHITELIST_ENABLED', false),
        'ip_whitelist' => env('IP_WHITELIST', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertes de sécurité
    |--------------------------------------------------------------------------
    |
    */
    'alerts' => [
        'enabled' => env('SECURITY_ALERTS_ENABLED', true),
        'channels' => ['log', 'email'],
        'admin_email' => env('ADMIN_EMAIL'),
        'suspicious_threshold' => env('SUSPICIOUS_ACTIVITY_THRESHOLD', 10),
    ],
];
