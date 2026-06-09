<?php

declare(strict_types=1);

namespace Modules\Article\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource Category
 *
 * Transforme l'objet Category en tableau JSON.
 */
class CategoryResource extends JsonResource
{
    /**
     * Transforme la resource en tableau
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'description' => $this->description,
            'icone' => $this->icone,
            'couleur' => $this->couleur,
            'niveau' => $this->niveau,
            'ordre' => $this->ordre,
            'chemin' => $this->chemin,
            'est_active' => $this->est_active,
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'nom' => $this->parent->nom,
                    'slug' => $this->parent->slug,
                ];
            }),
            'enfants' => $this->whenLoaded('enfants', function () {
                return CategoryResource::collection($this->enfants);
            }),
            'nombre_articles' => $this->when(
                $this->relationLoaded('articles'),
                $this->articles->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
