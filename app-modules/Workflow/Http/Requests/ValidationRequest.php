<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de validation multi-niveaux
 *
 * Valide les données pour les endpoints de validation.
 */
class ValidationRequest extends FormRequest
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
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'decision' => [
                'nullable',
                'string',
                'in:valider,publier,rejeter',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',
            'decision.in' => 'La décision doit être valider, publier ou rejeter.',
        ];
    }

    /**
     * Prépare les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('commentaire')) {
            $this->merge([
                'commentaire' => trim($this->commentaire),
            ]);
        }
    }
}
