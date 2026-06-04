<?php
// app-modules/User/Http/Resources/RoleResource.php

namespace Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource Role – Formate les données d'un rôle pour l'API.
 *
 * Cette classe transforme le modèle Role en un tableau JSON avec les informations
 * essentielles du rôle ainsi que des statistiques utiles (nombre de permissions,
 * nombre d'utilisateurs ayant ce rôle).
 *
 * Utile pour les dashboards et les interfaces d'administration où on veut
 * afficher un aperçu rapide des rôles du système.
 *
 * @see RoleController utilise cette ressource pour formater les rôles
 */
class RoleResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     *
     * Formate les données du rôle avec :
     * - Informations de base (id, name, display_name, description, level)
     * - Statistiques (permissions_count, users_count)
     * - Métadonnées (created_at)
     *
     * @param Request $request La requête HTTP (utilisée par Laravel pour le contexte)
     * @return array<string, mixed> Les données formatées du rôle
     */
    public function toArray(Request $request): array
    {
        return [
            // Identifiant unique du rôle
            'id' => $this->id,
            // Nom du rôle (machine-readable, ex: super_admin)
            'name' => $this->name,
            // Nom d'affichage (human-readable, ex: Super Administrateur)
            'display_name' => $this->display_name,
            // Description du rôle et ses responsabilités
            'description' => $this->description,
            // Niveau hiérarchique (8=super_admin, 1=lecteur)
            'level' => $this->level,
            // Nombre de permissions associées à ce rôle
            'permissions_count' => $this->permissions->count(),
            // Nombre d'utilisateurs ayant ce rôle
            'users_count' => $this->users->count(),
            // Date de création du rôle
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
