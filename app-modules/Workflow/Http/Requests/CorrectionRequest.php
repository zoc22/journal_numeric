<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de demande de correction
 */
class CorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'commentaires' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'annotations' => [
                'nullable',
                'array',
            ],
            'corrections_structurees' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaires.required' => 'Les commentaires de correction sont obligatoires',
            'commentaires.min' => 'Les commentaires doivent contenir au moins 10 caractères',
            'commentaires.max' => 'Les commentaires ne peuvent pas dépasser 2000 caractères',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('commentaires')) {
            $this->merge([
                'commentaires' => trim($this->commentaires),
            ]);
        }
    }
}
