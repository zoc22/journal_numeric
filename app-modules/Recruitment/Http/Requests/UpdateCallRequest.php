<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'titre' => [
                'sometimes',
                'string',
                'max:255',
                'min:3',
            ],
            'description' => [
                'sometimes',
                'string',
                'min:50',
            ],
            'roles_vises' => [
                'sometimes',
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
                'sometimes',
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
}
