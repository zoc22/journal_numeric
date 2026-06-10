<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de demande de correction
 *
 * Valide les données pour la demande de corrections par un reviewer.
 */
class CorrectionRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('reviewer');
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'commentaires' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'annotations' => [
                'nullable',
                'array',
            ],
            'annotations.*.position' => [
                'required_with:annotations',
                'integer',
                'min:0',
            ],
            'annotations.*.commentaire' => [
                'required_with:annotations',
                'string',
                'max:500',
            ],
            'annotations.*.type' => [
                'nullable',
                'string',
                'in:grammaire,style,contenu,structure,orthographe',
            ],
            'corrections_structurees' => [
                'nullable',
                'array',
            ],
            'corrections_structurees.*.section' => [
                'required_with:corrections_structurees',
                'string',
                'max:255',
            ],
            'corrections_structurees.*.description' => [
                'required_with:corrections_structurees',
                'string',
                'max:1000',
            ],
            'corrections_structurees.*.priorite' => [
                'nullable',
                'string',
                'in:basse,moyenne,haute,critique',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'commentaires.required' => 'Les commentaires de correction sont obligatoires.',
            'commentaires.min' => 'Les commentaires doivent contenir au moins 10 caractères.',
            'commentaires.max' => 'Les commentaires ne peuvent pas dépasser 2000 caractères.',
            'annotations.*.position.required_with' => 'La position de l\'annotation est obligatoire.',
            'annotations.*.commentaire.required_with' => 'Le commentaire de l\'annotation est obligatoire.',
        ];
    }
}
