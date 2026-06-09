<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de mise à jour d'article
 *
 * Valide les données pour la mise à jour d'un article existant.
 */
class UpdateArticleRequest extends FormRequest
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

        // Seul l'auteur ou un éditeur peut modifier
        return $this->user()->id === $article->auteur_id
               || $this->user()->hasRole(['editeur_associe', 'editeur_chef', 'admin_plateforme']);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'titre' => [
                'sometimes',
                'string',
                'max:500',
                'min:3',
            ],
            'contenu' => [
                'sometimes',
                'string',
                'min:50',
            ],
            'resume' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'image_principale' => [
                'nullable',
                'string',
                'max:500',
                'url',
            ],
            'galerie_medias' => [
                'nullable',
                'array',
            ],
            'meta_title' => [
                'nullable',
                'string',
                'max:120',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:320',
            ],
            'meta_keywords' => [
                'nullable',
                'string',
                'max:255',
            ],
            'categories' => [
                'nullable',
                'array',
                'exists:categories,id',
            ],
            'continent' => [
                'nullable',
                'string',
                'max:100',
            ],
            'pays' => [
                'nullable',
                'string',
                'max:100',
            ],
            'ville' => [
                'nullable',
                'string',
                'max:100',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'resume_modifications' => [
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
            'titre.min' => 'Le titre doit contenir au moins 3 caractères.',
            'contenu.min' => 'Le contenu doit contenir au moins 50 caractères.',
            'image_principale.url' => 'L\'URL de l\'image principale doit être valide.',
        ];
    }
}
