<?php

declare(strict_types=1);

namespace Modules\Article\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource ArticleVersion
 *
 * Transforme l'objet ArticleVersion en tableau JSON.
 */
class ArticleVersionResource extends JsonResource
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
            'numero_version' => $this->numero_version,
            'titre' => $this->titre,
            'resume' => $this->resume,
            'contenu' => $this->when(
                $request->user()?->hasRole(['editeur_associe', 'editeur_chef', 'admin_plateforme']),
                $this->contenu
            ),
            'image_principale' => $this->image_principale,
            'resume_modifications' => $this->resume_modifications,
            'est_publiee' => $this->est_publiee,
            'est_actuelle' => $this->est_actuelle,
            'cree_par' => [
                'id' => $this->createur?->id,
                'nom' => $this->createur?->nom,
            ],
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
