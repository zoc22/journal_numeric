<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Enums\ArticleStatus;

class WorkflowHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'statut_origine' => $this->statut_origine,
            'statut_origine_label' => ArticleStatus::fromString($this->statut_origine)->label(),
            'statut_cible' => $this->statut_cible,
            'statut_cible_label' => ArticleStatus::fromString($this->statut_cible)->label(),
            'utilisateur' => [
                'id' => $this->utilisateur?->id,
                'nom' => $this->utilisateur?->nom,
                'role_niveau' => $this->role_niveau,
            ],
            'commentaires' => $this->commentaires,
            'metadonnees' => $this->metadonnees,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
