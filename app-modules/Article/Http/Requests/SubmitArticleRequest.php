<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de soumission d'article
 *
 * Valide les données pour la soumission d'un article à relecture.
 */
class SubmitArticleRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        $article = $this->route('article');

        if (!$article) {
            return false;
        }

        // Seul l'auteur ou un éditeur peut soumettre
        return $this->user()->id === $article->auteur_id
               || $this->user()->hasRole(['editeur_associe', 'editeur_chef', 'admin_plateforme']);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'note_submission' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'note_submission.max' => 'La note de soumission ne peut pas dépasser 500 caractères.',
        ];
    }
}
