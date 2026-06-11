<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de création d'appel à candidatures
 */
class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titre' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'description' => [
                'required',
                'string',
                'min:50',
            ],
            'roles_vises' => [
                'required',
                'array',
                'min:1',
            ],
            'roles_vises.*' => [
                'string',
                Rule::in(['journaliste', 'reviewer', 'editeur_associe', 'directeur_collection', 'editeur_chef']),
            ],
            'conditions' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'prerequis' => [
                'nullable',
                'array',
            ],
            'date_limite' => [
                'required',
                'date',
                'after:today',
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
            'nombre_postes' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'metadonnees' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de l\'appel est obligatoire.',
            'titre.min' => 'Le titre doit contenir au moins 3 caractères.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 50 caractères.',
            'roles_vises.required' => 'Au moins un rôle visé est obligatoire.',
            'date_limite.required' => 'La date limite est obligatoire.',
            'date_limite.after' => 'La date limite doit être postérieure à aujourd\'hui.',
        ];
    }
}
