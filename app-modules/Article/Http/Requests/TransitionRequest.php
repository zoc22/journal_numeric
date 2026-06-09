<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de transition de workflow
 *
 * Valide les données pour une transition d'état d'article.
 */
class TransitionRequest extends FormRequest
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
        $statutsValides = [
            'soumis',
            'en_relecture',
            'correction_demandee',
            'valide_reviewer',
            'valide_editeur',
            'valide_directeur',
            'valide_final',
            'publie',
            'rejete',
            'archive',
        ];

        return [
            'statut_cible' => [
                'required',
                'string',
                Rule::in($statutsValides),
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'raison' => [
                'required_if:statut_cible,rejete',
                'nullable',
                'string',
                'max:500',
            ],
            'annotations' => [
                'nullable',
                'array',
            ],
            'metadonnees' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'statut_cible.required' => 'Le statut cible est obligatoire.',
            'statut_cible.in' => 'Le statut cible n\'est pas valide.',
            'raison.required_if' => 'La raison du rejet est obligatoire.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser 1000 caractères.',
        ];
    }

    /**
     * Prépare les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        // Nettoie le commentaire
        if ($this->has('commentaire')) {
            $this->merge([
                'commentaire' => trim($this->commentaire),
            ]);
        }

        // Nettoie la raison
        if ($this->has('raison')) {
            $this->merge([
                'raison' => trim($this->raison),
            ]);
        }
    }
}
