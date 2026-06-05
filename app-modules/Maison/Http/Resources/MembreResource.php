<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour transformer un membre de maison en réponse API.
 */
class MembreResource extends JsonResource
{
    /**
     * Transforme la ressource en tableau.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'maison_id' => $this->maison_id,
            'utilisateur' => [
                'id' => $this->utilisateur->id ?? null,
                'nom' => $this->utilisateur->nom ?? null,
                'prenom' => $this->utilisateur->prenom ?? null,
                'email' => $this->utilisateur->email ?? null,
                'avatar' => $this->utilisateur->avatar ?? null,
            ],
            'role' => [
                'id' => $this->role->id ?? null,
                'name' => $this->role->name ?? null,
                'level' => $this->role->level ?? null,
            ],
            'est_actif' => $this->est_actif,
            'a_rejoint_le' => $this->a_rejoint_le?->toISOString(),
            'a_quitte_le' => $this->a_quitte_le?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
