<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Recruitment\Enums\CallStatus;

class CallForApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $isEditeur = $user && $user->hasRole(['editeur_chef', 'editeur_associe', 'admin_plateforme']);

        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'description' => $this->description,
            'roles_vises' => $this->roles_vises,
            'conditions' => $this->conditions,
            'prerequis' => $this->prerequis,
            'date_limite' => $this->date_limite->toISOString(),
            'date_limite_formatee' => $this->date_limite->format('d/m/Y'),
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'statut' => $this->statut,
            'statut_label' => CallStatus::from($this->statut)->label(),
            'nombre_postes' => $this->nombre_postes,
            'est_actif' => $this->estOuvert(),
            'est_expire' => $this->estExpire(),
            'publie_le' => $this->publie_le?->toISOString(),
            'ferme_le' => $this->ferme_le?->toISOString(),
            'createur' => $this->whenLoaded('createur', function () {
                return [
                    'id' => $this->createur->id,
                    'nom' => $this->createur->nom,
                ];
            }),
            'statistiques' => $this->when($isEditeur && $this->relationLoaded('candidatures'), [
                'total' => $this->candidatures->count(),
                'en_attente' => $this->candidatures->where('statut', 'en_attente')->count(),
                'en_revue' => $this->candidatures->where('statut', 'en_revue')->count(),
                'acceptees' => $this->candidatures->where('statut', 'acceptee')->count(),
                'rejetees' => $this->candidatures->where('statut', 'rejetee')->count(),
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
