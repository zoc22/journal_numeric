<?php

declare(strict_types=1);

namespace Modules\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource ArticleMedia
 *
 * Transforme l'association article-média en tableau JSON.
 */
class ArticleMediaResource extends JsonResource
{
    /**
     * Transforme la resource en tableau
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type_usage' => $this->type_usage,
            'ordre_affichage' => $this->ordre_affichage,
            'est_actif' => $this->est_actif,
            'legende' => $this->legende,
            'credits' => $this->credits,
            'media' => new MediaResource($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
