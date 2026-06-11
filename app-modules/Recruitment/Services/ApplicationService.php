<?php

declare(strict_types=1);

namespace Modules\Recruitment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Models\Application;
use Modules\Recruitment\Models\CallForApplication;
use Modules\User\Models\User;

/**
 * Service de gestion des candidatures
 *
 * Centralise la logique métier pour :
 * - Soumission de candidatures
 * - Revue et décision
 * - Gestion des permissions
 */
class ApplicationService
{
    /**
     * Soumet une nouvelle candidature
     *
     * @param CallForApplication $call
     * @param User $candidat
     * @param array $data
     * @return Application
     */
    public function soumettre(CallForApplication $call, User $candidat, array $data): Application
    {
        // Vérifie que l'appel accepte encore les candidatures
        if (!$call->accepteCandidatures()) {
            throw new \DomainException('Cet appel à candidatures n\'est plus ouvert.');
        }

        // Vérifie que le candidat n'a pas déjà postulé
        $existing = Application::where('appel_id', $call->id)
            ->where('candidat_id', $candidat->id)
            ->exists();

        if ($existing) {
            throw new \DomainException('Vous avez déjà postulé à cet appel.');
        }

        return DB::transaction(function () use ($call, $candidat, $data) {
            $application = Application::create([
                'appel_id' => $call->id,
                'candidat_id' => $candidat->id,
                'lettre_motivation' => $data['lettre_motivation'],
                'experiences' => $data['experiences'] ?? null,
                'formations' => $data['formations'] ?? null,
                'portfolio' => $data['portfolio'] ?? null,
                'competences' => $data['competences'] ?? null,
                'documents' => $data['documents'] ?? null,
                'cv_url' => $data['cv_url'] ?? null,
                'continent' => $data['continent'] ?? null,
                'pays' => $data['pays'] ?? null,
                'ville' => $data['ville'] ?? null,
                'statut' => ApplicationStatus::EN_ATTENTE->value,
            ]);

            Event::dispatch(new \Modules\Recruitment\Events\ApplicationSubmitted($application, $candidat));

            return $application;
        });
    }

    /**
     * Met à jour une candidature (seulement si en attente)
     *
     * @param Application $application
     * @param array $data
     * @return Application
     */
    public function mettreAJour(Application $application, array $data): Application
    {
        if (!$application->estModifiable()) {
            throw new \DomainException('Cette candidature ne peut plus être modifiée.');
        }

        $updatable = ['lettre_motivation', 'experiences', 'formations', 'portfolio', 'competences', 'documents', 'cv_url', 'continent', 'pays', 'ville'];

        foreach ($updatable as $field) {
            if (isset($data[$field])) {
                $application->$field = $data[$field];
            }
        }

        $application->save();

        return $application;
    }

    /**
     * Met une candidature en revue
     *
     * @param Application $application
     * @param User $examinateur
     * @return Application
     */
    public function mettreEnRevue(Application $application, User $examinateur): Application
    {
        if (!$application->passerEnRevue($examinateur)) {
            throw new \DomainException('Impossible de mettre cette candidature en revue.');
        }

        return $application;
    }

    /**
     * Accepte une candidature
     *
     * @param Application $application
     * @param User $examinateur
     * @param string|null $commentaires
     * @param int|null $score
     * @return Application
     */
    public function accepter(Application $application, User $examinateur, ?string $commentaires = null, ?int $score = null): Application
    {
        if (!$application->accepter($examinateur, $commentaires, $score)) {
            throw new \DomainException('Impossible d\'accepter cette candidature.');
        }

        Event::dispatch(new \Modules\Recruitment\Events\ApplicationReviewed($application, $examinateur, 'acceptee'));

        return $application;
    }

    /**
     * Rejette une candidature
     *
     * @param Application $application
     * @param User $examinateur
     * @param string|null $commentaires
     * @return Application
     */
    public function rejeter(Application $application, User $examinateur, ?string $commentaires = null): Application
    {
        if (!$application->rejeter($examinateur, $commentaires)) {
            throw new \DomainException('Impossible de rejeter cette candidature.');
        }

        Event::dispatch(new \Modules\Recruitment\Events\ApplicationReviewed($application, $examinateur, 'rejetee'));

        return $application;
    }

    /**
     * Récupère les candidatures d'un appel
     *
     * @param CallForApplication $call
     * @param string|null $statut
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCandidaturesParAppel(CallForApplication $call, ?string $statut = null)
    {
        $query = $call->candidatures()->with('candidat');

        if ($statut) {
            $query->where('statut', $statut);
        }

        return $query->orderBy('soumise_le', 'desc')->get();
    }

    /**
     * Récupère les candidatures d'un candidat
     *
     * @param User $candidat
     * @param string|null $statut
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCandidaturesParCandidat(User $candidat, ?string $statut = null)
    {
        $query = Application::with('appel')
            ->where('candidat_id', $candidat->id);

        if ($statut) {
            $query->where('statut', $statut);
        }

        return $query->orderBy('soumise_le', 'desc')->get();
    }

    /**
     * Récupère les statistiques d'un appel
     *
     * @param CallForApplication $call
     * @return array
     */
    public function getStatistiques(CallForApplication $call): array
    {
        $stats = [
            'total' => $call->candidatures()->count(),
            'en_attente' => $call->candidatures()->where('statut', 'en_attente')->count(),
            'en_revue' => $call->candidatures()->where('statut', 'en_revue')->count(),
            'acceptees' => $call->candidatures()->where('statut', 'acceptee')->count(),
            'rejetees' => $call->candidatures()->where('statut', 'rejetee')->count(),
        ];

        $stats['taux_acceptation'] = $stats['total'] > 0
            ? round(($stats['acceptees'] / $stats['total']) * 100, 2)
            : 0;

        return $stats;
    }

    /**
     * Vérifie si un candidat a déjà postulé à un appel
     *
     * @param string $appelId
     * @param string $candidatId
     * @return bool
     */
    public function aDejaPostule(string $appelId, string $candidatId): bool
    {
        return Application::where('appel_id', $appelId)
            ->where('candidat_id', $candidatId)
            ->exists();
    }
}
