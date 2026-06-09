<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête d'assignation de reviewer
 */
class AssignReviewerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $isStore = $this->isMethod('POST') && !$this->route('assignment');

        return [
            'article_id' => [
                $isStore ? 'required' : 'nullable',
                'exists:articles,id',
            ],
            'reviewer_id' => [
                $isStore ? 'required' : 'nullable',
                'exists:users,id',
            ],
            'ordre_review' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
            'raison' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'article_id.required' => 'L\'article est obligatoire',
            'article_id.exists' => 'L\'article sélectionné n\'existe pas',
            'reviewer_id.required' => 'Le reviewer est obligatoire',
            'reviewer_id.exists' => 'Le reviewer sélectionné n\'existe pas',
            'ordre_review.min' => 'L\'ordre doit être au minimum 1',
            'ordre_review.max' => 'L\'ordre ne peut pas dépasser 10',
        ];
    }
}
