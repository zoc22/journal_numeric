<?php

declare(strict_types=1);

namespace Modules\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de mise à jour des paramètres de sécurité
 *
 * Valide les données pour la configuration des paramètres de sécurité.
 */
class SecuritySettingsRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole(['super_admin', 'admin_plateforme']);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            // Paramètres de logging
            'logging_enabled' => [
                'nullable',
                'boolean',
            ],
            'log_retention_days' => [
                'nullable',
                'integer',
                'min:7',
                'max:365',
            ],
            'log_sensitive_actions' => [
                'nullable',
                'boolean',
            ],
            'log_all_actions' => [
                'nullable',
                'boolean',
            ],

            // Paramètres de rate limiting
            'rate_limiting_enabled' => [
                'nullable',
                'boolean',
            ],
            'max_login_attempts' => [
                'nullable',
                'integer',
                'min:3',
                'max:20',
            ],
            'max_api_attempts' => [
                'nullable',
                'integer',
                'min:10',
                'max:500',
            ],
            'lockout_time_minutes' => [
                'nullable',
                'integer',
                'min:5',
                'max:120',
            ],

            // Paramètres de sessions
            'session_tracking_enabled' => [
                'nullable',
                'boolean',
            ],
            'max_concurrent_sessions' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'session_timeout_minutes' => [
                'nullable',
                'integer',
                'min:15',
                'max:480',
            ],

            // Paramètres de sécurité
            'password_min_length' => [
                'nullable',
                'integer',
                'min:6',
                'max:20',
            ],
            'require_strong_password' => [
                'nullable',
                'boolean',
            ],
            'two_factor_enabled' => [
                'nullable',
                'boolean',
            ],

            // Paramètres d'alertes
            'security_alerts_enabled' => [
                'nullable',
                'boolean',
            ],
            'alert_channels' => [
                'nullable',
                'array',
            ],
            'alert_channels.*' => [
                'string',
                Rule::in(['log', 'email', 'slack', 'webhook']),
            ],
            'admin_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'suspicious_threshold' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'log_retention_days.min' => 'La période de rétention des logs doit être d\'au moins 7 jours.',
            'log_retention_days.max' => 'La période de rétention des logs ne peut pas dépasser 365 jours.',
            'max_login_attempts.min' => 'Le nombre maximum de tentatives de connexion doit être d\'au moins 3.',
            'max_login_attempts.max' => 'Le nombre maximum de tentatives de connexion ne peut pas dépasser 20.',
            'lockout_time_minutes.min' => 'La durée de verrouillage doit être d\'au moins 5 minutes.',
            'max_concurrent_sessions.min' => 'Le nombre maximum de sessions simultanées doit être d\'au moins 1.',
            'password_min_length.min' => 'La longueur minimale du mot de passe doit être d\'au moins 6 caractères.',
            'admin_email.email' => 'L\'adresse email de l\'administrateur doit être valide.',
            'suspicious_threshold.min' => 'Le seuil de suspicion doit être d\'au moins 1.',
        ];
    }

    /**
     * Prépare les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        // Conversion des valeurs "on"/"off" en booléens
        $booleanFields = [
            'logging_enabled', 'log_sensitive_actions', 'log_all_actions',
            'rate_limiting_enabled', 'session_tracking_enabled',
            'require_strong_password', 'two_factor_enabled', 'security_alerts_enabled',
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->$field;
                if (is_string($value)) {
                    $this->merge([
                        $field => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                    ]);
                }
            }
        }
    }
}
