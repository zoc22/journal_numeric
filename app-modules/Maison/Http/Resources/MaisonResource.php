<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour transformer une maison d'édition en réponse API.
 */
class MaisonResource extends JsonResource
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
            'nom' => $this->nom,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'email_contact' => $this->email_contact,
            // On utilise l'attribut calculé statut_libelle défini dans le modèle Maison
            'statut' => $this->when(!$request->routeIs('maison.public.*'), $this->statut),
            'statut_libelle' => $this->when(!$request->routeIs('maison.public.*'), $this->statut_libelle),
            'validee_par' => $this->when(!$request->routeIs('maison.public.*') && $this->relationLoaded('validateur') && $this->validateur, function () {
                return [
                    'id' => $this->validateur->id,
                    'nom' => $this->validateur->nom,
                    'prenom' => $this->validateur->prenom,
                    'email' => $this->validateur->email,
                ];
            }),
            'validee_le' => $this->when(!$request->routeIs('maison.public.*'), $this->validee_le?->toISOString()),
            'est_validee' => $this->estValidee(),
            'nombre_membres' => $this->whenCounted('membres', $this->membres_count ?? 0),
            // 'nombre_articles' => $this->whenCounted('articles', $this->articles_count ?? 0),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
