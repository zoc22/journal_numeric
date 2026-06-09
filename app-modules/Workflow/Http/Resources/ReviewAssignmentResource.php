<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Enums\ReviewStatus;

class ReviewAssignmentResource extends JsonResource
{
    public static $wrap = 'assignment';

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'article' => [
                'id' => $this->article?->id,
                'titre' => $this->article?->titre,
                'slug' => $this->article?->slug,
            ],
            'reviewer' => [
                'id' => $this->reviewer?->id,
                'nom' => $this->reviewer?->nom,
                'email' => $this->reviewer?->email,
            ],
            'assigne_par' => [
                'id' => $this->assignePar?->id,
                'nom' => $this->assignePar?->nom,
            ],
            'ordre_review' => $this->ordre_review,
            'statut' => $this->statut,
            'statut_label' => ReviewStatus::from($this->statut)->label(),
            'assigne_le' => $this->assigne_le?->toISOString(),
            'accepte_le' => $this->accepte_le?->toISOString(),
            'deadline' => $this->deadline?->toISOString(),
            'termine_le' => $this->termine_le?->toISOString(),
            'feedback' => ReviewFeedbackResource::collection($this->whenLoaded('feedback')),
            'est_expiree' => $this->estExpiree(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
