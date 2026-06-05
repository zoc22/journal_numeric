<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Request pour l'ajout d'un membre à une maison d'édition.
 *
 * @property string $utilisateur_id ID de l'utilisateur à ajouter
 * @property string $role_id ID du rôle à assigner
 */
class AddMembreRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     * Seul l'Éditeur en Chef ou un Admin peut ajouter des membres.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        // Super Admin peut tout faire
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin plateforme peut ajouter des membres
        if ($user->hasRole('admin_plateforme')) {
            return true;
        }

        // Pour les membres de maison, on récupère la maison de l'utilisateur
        $membre = $user->membres()->where('est_actif', true)->first();
        if (!$membre) {
            return false;
        }

        $maison = $membre->maison;

        // Éditeur en Chef peut ajouter des membres à sa maison
        if ($maison && $maison->aLeRole($user->id, 'editeur_en_chef')) {
            return true;
        }

        return false;
    }

    /**
     * Règles de validation.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maison = $this->route('maison');

        return [
            'utilisateur_id' => [
                'required',
                'string',
                'exists:' . User::class . ',id',
                Rule::unique('membre_maison', 'utilisateur_id')
                    ->where('maison_id', $maison?->id)
                    ->whereNull('a_quitte_le'),
            ],
            'role_id' => [
                'required',
                'exists:roles,id',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'utilisateur_id.required' => 'L\'ID de l\'utilisateur est obligatoire.',
            'utilisateur_id.exists' => 'L\'utilisateur spécifié n\'existe pas.',
            'utilisateur_id.unique' => 'Cet utilisateur est déjà membre de cette maison.',
            'role_id.required' => 'Le rôle est obligatoire.',
            'role_id.exists' => 'Le rôle spécifié n\'existe pas.',
        ];
    }
}
