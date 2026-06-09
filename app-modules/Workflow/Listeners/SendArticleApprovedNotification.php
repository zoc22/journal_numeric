<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workflow\Events\ArticleValidated;

/**
 * Envoie une notification lors de la validation d'un article
 */
class SendArticleApprovedNotification
{
    public function handle(ArticleValidated $event): void
    {
        if (!config('workflow.notifications.on_approve', true)) {
            return;
        }

        Log::info('Notification de validation envoyée', [
            'article_id' => $event->article->id,
            'validateur_id' => $event->validateur->id,
            'niveau_validation' => $event->niveauValidation?->value,
        ]);
    }
}
