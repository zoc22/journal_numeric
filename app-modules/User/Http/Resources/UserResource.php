<?php
// app-modules/User/Http/Resources/UserResource.php

namespace Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource User – Formate les données d'un utilisateur pour l'API.
 *
 * Cette classe transforme le modèle User en un tableau JSON
 * avec seulement les champs nécessaires pour l'API.
 *
 * On cache volontairement les informations sensibles comme
 * le mot de passe, le token de connexion, etc.
 */
class UserResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     *
     * Cette méthode formate les données du modèle User en un tableau JSON
     * adapté pour l'API. Elle inclut :
     * - Les informations de base (id, nom, email, etc.)
     * - Les métadonnées de connexion (last_login_at, last_login_ip)
     * - Les rôles et permissions de l'utilisateur
     * - L'accès au back-office
     *
     * Les informations sensibles (password) sont exclues automatiquement
     * par le modèle User (attribut $hidden).
     *
     * @param Request $request La requête HTTP (utilisée par Laravel pour le contexte)
     * @return array<string, mixed> Les données formatées
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiants et informations de base
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'full_name' => $this->full_name,

            // Informations de contact
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'telephone' => $this->telephone,
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,

            // Informations professionnelles
            'poste' => $this->poste,
            'avatar' => $this->avatar,

            // Statut
            'is_active' => $this->is_active,

            // Informations de connexion
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_login_ip' => $this->last_login_ip,

            // Métadonnées de création/modification
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Informations sur les rôles et permissions
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getDirectPermissions()->pluck('name'),

            // Indicateur d'accès au back-office
            'can_access_backoffice' => $this->canAccessBackoffice(),
        ];
    }
}
