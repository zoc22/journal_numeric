<?php
// app-modules/User/Http/Requests/AssignRoleRequest.php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Requête pour l'assignation ou le retrait d'un rôle.
 *
 * Très simple : nécessite seulement le nom du rôle.
 */
class AssignRoleRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|string|exists:roles,name',
        ];
    }

    /**
     * Messages d'erreur.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Le nom du rôle est obligatoire.',
            'role.exists' => 'Le rôle spécifié n\'existe pas.',
        ];
    }
}
