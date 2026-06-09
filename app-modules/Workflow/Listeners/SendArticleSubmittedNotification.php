<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Workflow\Events\ArticleSubmitted;

/**
 * Envoie une notification lors de la soumission d'un article
 */
class SendArticleSubmittedNotification
{
    public function handle(ArticleSubmitted $event): void
    {
        if (!config('workflow.notifications.on_submit', true)) {
            return;
        }

        // Log l'envoi de notification
        Log::info('Notification de soumission envoyée', [
            'article_id' => $event->article->id,
            'auteur_id' => $event->article->auteur_id,
        ]);

        // Ici, implémenter l'envoi d'email aux éditeurs
        // Mail::to($editeurs)->send(new ArticleSubmittedMail($event->article));
    }
}
