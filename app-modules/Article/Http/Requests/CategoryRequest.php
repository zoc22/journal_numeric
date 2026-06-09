<?php

declare(strict_types=1);

namespace Modules\Article\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de gestion des catégories
 *
 * Valide les données pour la création et mise à jour des catégories.
 */
class CategoryRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        return $this->user() !== null
               && $this->user()->hasRole(['editeur_associe', 'editeur_chef', 'admin_plateforme']);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        $rules = [
            'nom' => [
                $this->isMethod('POST') ? 'required' : 'sometimes',
                'string',
                'max:255',
                'min:2',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'icone' => [
                'nullable',
                'string',
                'max:100',
            ],
            'couleur' => [
                'nullable',
                'string',
                'regex:/^#[a-fA-F0-9]{6}$/',
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
            'ordre' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'est_active' => [
                'nullable',
                'boolean',
            ],
        ];

        // Règle d'unicité du slug pour la mise à jour
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $category = $this->route('category');
            $rules['slug'] = [
                'sometimes',
                'string',
                'max:280',
                Rule::unique('categories', 'slug')->ignore($category?->id),
            ];
        }

        return $rules;
    }

    /**
     * Messages de validation
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la catégorie est obligatoire.',
            'nom.min' => 'Le nom doit contenir au moins 2 caractères.',
            'parent_id.exists' => 'La catégorie parente sélectionnée n\'existe pas.',
            'couleur.regex' => 'La couleur doit être au format hexadécimal (ex: #FF0000).',
        ];
    }
}
