<?php
// app-modules/User/Http/Requests/UpdateUserRequest.php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête de mise à jour d'utilisateur.
 *
 * Tous les champs sont optionnels car on peut
 * mettre à jour seulement certains champs.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     *
     * L'autorisation est gérée par le middleware au niveau de la route.
     *
     * @return bool True si l'utilisateur est autorisé
     */
    public function authorize(): bool
    {
        // L'autorisation est gérée par le middleware 'permission:user.manage'
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'utilisateur.
     *
     * Tous les champs sont optionnels (sometimes) car on peut mettre à jour
     * seulement certains champs sans modifier les autres.
     *
     * Cas spécial : L'email doit être unique sauf pour l'utilisateur courant
     * (pour éviter de bloquer sa propre adresse email).
     *
     * @return array<string, mixed> Les règles de validation
     */
    public function rules(): array
    {
        // Récupère l'ID de l'utilisateur depuis la route
        $user = $this->route('user');
        $userId = $user instanceof \Modules\User\Models\User ? $user->id : $user;

        return [
            // Nom optionnel
            'nom' => 'sometimes|string|max:255',
            // Prénom optionnel
            'prenom' => 'sometimes|string|max:255',
            // Email optionnel mais unique sauf pour cet utilisateur
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            // Mot de passe optionnel avec confirmation
            'password' => 'sometimes|string|min:8|confirmed',
            // Téléphone optionnel
            'telephone' => 'sometimes|string|max:20',
            // Continent, Pays, Ville optionnels en mise à jour
            'continent' => 'sometimes|string|max:255',
            'pays' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            // Poste optionnel
            'poste' => 'sometimes|string|max:255',
            // Statut actif optionnel (booléen)
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Messages d'erreur personnalisés (en français).
     *
     * @return array<string, string> Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'email.email' => 'Veuillez fournir une adresse email valide.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'continent.string' => 'Le continent doit être une chaîne de caractères.',
            'pays.string' => 'Le pays doit être une chaîne de caractères.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',
        ];
    }
}
