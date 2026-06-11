<?php

declare(strict_types=1);

namespace Modules\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de filtrage des logs d'audit
 *
 * Valide les paramètres de filtrage pour la consultation des logs.
 */
class AuditLogRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'module' => [
                'nullable',
                'string',
                Rule::in(['auth', 'article', 'workflow', 'user', 'role', 'permission', 'media', 'recruitment', 'security']),
            ],
            'action' => [
                'nullable',
                'string',
                Rule::in(['create', 'update', 'delete', 'login', 'logout', 'publish', 'submit', 'validate', 'reject', 'assign', 'upload']),
            ],
            'niveau' => [
                'nullable',
                'string',
                Rule::in(['info', 'warning', 'critical']),
            ],
            'utilisateur_id' => [
                'nullable',
                'uuid',
                'exists:users,id',
            ],
            'maison_id' => [
                'nullable',
                'uuid',
                'exists:tenants,id',
            ],
            'date_debut' => [
                'nullable',
                'date',
                'before_or_equal:date_fin',
            ],
            'date_fin' => [
                'nullable',
                'date',
                'after_or_equal:date_debut',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:500',
            ],
            'order_by' => [
                'nullable',
                'string',
                Rule::in(['created_at', 'action', 'module', 'niveau']),
            ],
            'order_dir' => [
                'nullable',
                'string',
                Rule::in(['asc', 'desc']),
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'module.in' => 'Le module spécifié n\'est pas valide.',
            'action.in' => 'L\'action spécifiée n\'est pas valide.',
            'niveau.in' => 'Le niveau de criticité spécifié n\'est pas valide.',
            'utilisateur_id.uuid' => 'L\'ID utilisateur doit être un UUID valide.',
            'utilisateur_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
            'date_debut.before_or_equal' => 'La date de début doit être antérieure ou égale à la date de fin.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'per_page.max' => 'Le nombre d\'éléments par page ne peut pas dépasser 500.',
        ];
    }

    /**
     * Prépare les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        // Nettoie la recherche
        if ($this->has('search')) {
            $this->merge([
                'search' => trim($this->search),
            ]);
        }

        // Définit les valeurs par défaut
        if (!$this->has('order_by')) {
            $this->merge(['order_by' => 'created_at']);
        }

        if (!$this->has('order_dir')) {
            $this->merge(['order_dir' => 'desc']);
        }
    }
}
