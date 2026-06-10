<?php

declare(strict_types=1);

namespace Modules\Media\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource Media
 *
 * Transforme l'objet Media en tableau JSON pour l'API.
 */
class MediaResource extends JsonResource
{
    /**
     * Transforme la resource en tableau
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom_fichier' => $this->nom_fichier,
            'nom_original' => $this->nom_original,
            'url' => $this->url,
            'urls' => [
                'original' => $this->url,
                'large' => $this->getVariantUrl('large'),
                'medium' => $this->getVariantUrl('medium'),
                'small' => $this->getVariantUrl('small'),
                'thumbnail' => $this->getVariantUrl('thumbnail'),
            ],
            'type' => $this->type,
            'type_mime' => $this->type_mime,
            'extension' => $this->extension,
            'taille' => $this->taille,
            'taille_formatee' => $this->taille_formatee,
            'dimensions' => $this->dimensions,
            'est_publique' => $this->est_publique,
            'televerseur' => [
                'id' => $this->televerseur?->id,
                'nom' => $this->televerseur?->nom,
            ],
            'localisation' => [
                'continent' => $this->continent,
                'pays' => $this->pays,
                'ville' => $this->ville,
            ],
            'metadonnees' => $this->metadonnees,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
