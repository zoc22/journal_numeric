<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewApplicationRequest extends FormRequest
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
                Rule::in(['acceptee', 'rejetee']),
            ],
            'commentaires_examen' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'score' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La décision est obligatoire.',
            'decision.in' => 'La décision doit être acceptee ou rejetee.',
            'commentaires_examen.max' => 'Les commentaires ne peuvent pas dépasser 2000 caractères.',
            'score.min' => 'Le score doit être compris entre 0 et 100.',
            'score.max' => 'Le score doit être compris entre 0 et 100.',
        ];
    }
}
