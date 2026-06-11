<?php

declare(strict_types=1);

namespace Modules\Security\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'module' => $this->module,
            'description' => $this->description,
            'niveau' => $this->niveau,
            'niveau_label' => $this->niveau_label,
            'niveau_couleur' => $this->niveau_couleur,
            'utilisateur' => [
                'id' => $this->utilisateur?->id,
                'nom' => $this->utilisateur_nom ?? $this->utilisateur?->nom,
                'role' => $this->utilisateur_role,
            ],
            'entite_type' => $this->entite_type,
            'entite_id' => $this->entite_id,
            'anciennes_valeurs' => $this->anciennes_valeurs,
            'nouvelles_valeurs' => $this->nouvelles_valeurs,
            'champs_modifies' => $this->champs_modifies,
            'maison_id' => $this->maison_id,
            'contexte' => $this->contexte,
            'ip_address' => $this->ip_address,
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
