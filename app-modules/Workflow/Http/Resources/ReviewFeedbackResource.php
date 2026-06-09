<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewFeedbackResource extends JsonResource
{
    public static $wrap = 'feedback';

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'numero_iteration' => $this->numero_iteration,
            'decision' => $this->decision,
            'commentaire_global' => $this->commentaire_global,
            'annotations' => $this->annotations,
            'corrections_demandees' => $this->corrections_demandees,
            'score_qualite' => $this->score_qualite,
            'reviewer' => [
                'id' => $this->reviewer?->id,
                'nom' => $this->reviewer?->nom,
            ],
            'retour_le' => $this->retour_le?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
