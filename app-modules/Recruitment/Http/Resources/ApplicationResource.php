<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Recruitment\Enums\ApplicationStatus;

class ApplicationResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();
        $isEditeur = $user && $user->hasRole(['editeur_chef', 'editeur_associe', 'admin_plateforme']);
        $isProprietaire = $user && $user->id === $this->candidat_id;

        return [
            'id' => $this->id,
            'lettre_motivation' => $this->lettre_motivation,
            'experiences' => $this->experiences,
            'formations' => $this->formations,
            'portfolio' => $this->portfolio,
            'competences' => $this->competences,
            'documents' => $this->documents,
            'cv_url' => $this->cv_url,
            'continent' => $this->continent,
            'pays' => $this->pays,
            'ville' => $this->ville,
            'statut' => $this->statut,
            'statut_label' => ApplicationStatus::from($this->statut)->label(),
            'score' => $this->when($isEditeur, $this->score),
            'commentaires_examen' => $this->when($isEditeur, $this->commentaires_examen),
            'soumise_le' => $this->soumise_le->toISOString(),
            'examinee_le' => $this->examinee_le?->toISOString(),
            'est_modifiable' => $this->estModifiable(),
            'appel' => $this->whenLoaded('appel', function () {
                return [
                    'id' => $this->appel->id,
                    'titre' => $this->appel->titre,
                ];
            }),
            'candidat' => $this->when($isEditeur && $this->relationLoaded('candidat'), function () {
                return [
                    'id' => $this->candidat->id,
                    'nom' => $this->candidat->nom,
                    'email' => $this->candidat->email,
                ];
            }),
            'examinateur' => $this->when($isEditeur && $this->relationLoaded('examinateur'), function () {
                return $this->examinateur ? [
                    'id' => $this->examinateur->id,
                    'nom' => $this->examinateur->nom,
                ] : null;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
