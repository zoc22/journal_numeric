<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de création d'article
 *
 * Valide les données pour la création d'un nouvel article.
 */
class StoreArticleRequest extends FormRequest
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
            'titre' => [
                'required',
                'string',
                'max:500',
                'min:3',
            ],
            'contenu' => [
                'required',
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
            'galerie_medias.*' => [
                'string',
                'url',
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
            'tags.*' => [
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de l\'article est obligatoire.',
            'titre.min' => 'Le titre doit contenir au moins 3 caractères.',
            'contenu.required' => 'Le contenu de l\'article est obligatoire.',
            'contenu.min' => 'Le contenu doit contenir au moins 50 caractères.',
            'image_principale.url' => 'L\'URL de l\'image principale doit être valide.',
            'categories.exists' => 'Une ou plusieurs catégories sélectionnées sont invalides.',
        ];
    }

    /**
     * Prépare les données pour la validation
     */
    protected function prepareForValidation(): void
    {
        // Nettoie le contenu HTML
        if ($this->has('contenu')) {
            $this->merge([
                'contenu' => $this->nettoyerContenu($this->contenu),
            ]);
        }

        // Nettoie le titre
        if ($this->has('titre')) {
            $this->merge([
                'titre' => trim($this->titre),
            ]);
        }
    }

    /**
     * Nettoie le contenu HTML
     *
     * @param string $contenu
     * @return string
     */
    private function nettoyerContenu(string $contenu): string
    {
        // Supprime les tags XSS potentiels
        $contenu = strip_tags($contenu, '<p><br><b><strong><i><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><figure><figcaption><blockquote><pre><code>');

        return trim($contenu);
    }
}
