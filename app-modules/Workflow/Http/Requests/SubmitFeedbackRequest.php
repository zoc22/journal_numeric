<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête de soumission de feedback
 */
class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in(['correction', 'validation', 'rejet']),
            ],
            'commentaire_global' => [
                'nullable',
                'string',
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
            'corrections_demandees' => [
                'nullable',
                'array',
            ],
            'score_qualite' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La décision est obligatoire',
            'decision.in' => 'La décision doit être correction, validation ou rejet',
            'commentaire_global.max' => 'Le commentaire ne peut pas dépasser 2000 caractères',
            'score_qualite.min' => 'Le score de qualité doit être entre 1 et 5',
            'score_qualite.max' => 'Le score de qualité doit être entre 1 et 5',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('commentaire_global')) {
            $this->merge([
                'commentaire_global' => trim($this->commentaire_global),
            ]);
        }
    }
}
