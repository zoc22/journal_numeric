<?php

declare(strict_types=1);

namespace Modules\Article\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workflow\Http\Resources\WorkflowTransitionResource;

/**
 * Resource Article
 *
 * Transforme l'objet Article en tableau JSON pour l'API.
 * Permet de contrôler quelles données sont exposées.
 */
class ArticleResource extends JsonResource
{
    /**
     * Indique si la resource doit être enveloppée
     */
    public static $wrap = 'article';

    /**
     * Transforme la resource en tableau
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'titre' => $this->titre,
            'slug' => $this->slug,
            'resume' => $this->resume,
            'contenu' => $this->when(
                $this->shouldIncludeContent($request),
                $this->contenu
            ),
            'image_principale' => $this->image_principale,
            'galerie_medias' => $this->galerie_medias,
            'temps_lecture' => $this->temps_lecture,
            'temps_lecture_formate' => $this->temps_lecture_formate,
            'statut' => $this->statut,
            'nb_vues' => $this->nb_vues,
            'nb_partages' => $this->nb_partages,
            'nb_likes' => $this->nb_likes,
            'nb_commentaires' => $this->nb_commentaires,
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'url' => $this->url,
            'publie_le' => $this->publie_le?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];

        // Inclut les relations si demandé
        if ($this->whenLoaded('auteur')) {
            $data['auteur'] = [
                'id' => $this->auteur->id,
                'nom' => $this->auteur->nom,
                'email' => $this->auteur->email,
            ];
        }

        if ($this->whenLoaded('categories')) {
            $data['categories'] = CategoryResource::collection($this->categories);
        }

        if ($this->whenLoaded('tags')) {
            $data['tags'] = $this->tags->pluck('nom');
        }

        if ($this->whenLoaded('versions')) {
            $data['versions'] = ArticleVersionResource::collection(
                $this->versions->take(5)
            );
        }

        if ($this->whenLoaded('versionActuelle')) {
            $data['version_actuelle'] = new ArticleVersionResource($this->versionActuelle);
        }

        if ($this->whenLoaded('transitionsWorkflow')) {
            $data['historique_workflow'] = WorkflowTransitionResource::collection(
                $this->transitionsWorkflow->take(10)
            );
        }

        // Métadonnées SEO
        if ($this->meta_title || $this->meta_description) {
            $data['seo'] = [
                'meta_title' => $this->meta_title ?? $this->titre,
                'meta_description' => $this->meta_description ?? $this->resume,
                'meta_keywords' => $this->meta_keywords,
            ];
        }

        return $data;
    }

    /**
     * Détermine si le contenu doit être inclus
     *
     * Le contenu complet n'est inclus que si l'utilisateur est authentifié
     * ou si l'article est publié.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldIncludeContent(Request $request): bool
    {
        // Si l'article est publié, tout le monde peut voir le contenu
        if ($this->statut === 'publie') {
            return true;
        }

        // Sinon, seul l'auteur ou un éditeur peut voir le contenu
        $user = $request->user();

        return $user && (
            $user->id === $this->auteur_id ||
            $user->hasRole(['editeur_associe', 'editeur_chef', 'admin_plateforme', 'reviewer'])
        );
    }
}
