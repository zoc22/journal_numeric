<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'lettre_motivation' => [
                'required',
                'string',
                'min:100',
                'max:5000',
            ],
            'experiences' => [
                'nullable',
                'array',
            ],
            'experiences.*.poste' => [
                'required_with:experiences',
                'string',
                'max:255',
            ],
            'experiences.*.entreprise' => [
                'required_with:experiences',
                'string',
                'max:255',
            ],
            'experiences.*.date_debut' => [
                'required_with:experiences',
                'date',
            ],
            'experiences.*.date_fin' => [
                'nullable',
                'date',
                'after:date_debut',
            ],
            'formations' => [
                'nullable',
                'array',
            ],
            'formations.*.diplome' => [
                'required_with:formations',
                'string',
                'max:255',
            ],
            'formations.*.etablissement' => [
                'required_with:formations',
                'string',
                'max:255',
            ],
            'formations.*.annee' => [
                'required_with:formations',
                'integer',
                'min:1900',
                'max:' . date('Y'),
            ],
            'portfolio' => [
                'nullable',
                'array',
            ],
            'portfolio.*' => [
                'url',
            ],
            'competences' => [
                'nullable',
                'array',
            ],
            'competences.*' => [
                'string',
                'max:100',
            ],
            'cv_url' => [
                'nullable',
                'url',
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
            'documents' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lettre_motivation.required' => 'La lettre de motivation est obligatoire.',
            'lettre_motivation.min' => 'La lettre de motivation doit contenir au moins 100 caractères.',
            'portfolio.*.url' => 'Chaque lien du portfolio doit être une URL valide.',
            'cv_url.url' => 'L\'URL du CV doit être valide.',
        ];
    }
}
