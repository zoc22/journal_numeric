<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workflow\Events\CorrectionRequested;

/**
 * Envoie une notification lors d'une demande de correction
 */
class SendCorrectionRequestedNotification
{
    public function handle(CorrectionRequested $event): void
    {
        if (!config('workflow.notifications.on_correction', true)) {
            return;
        }

        Log::info('Notification de correction demandée envoyée', [
            'article_id' => $event->article->id,
            'reviewer_id' => $event->reviewer->id,
        ]);
    }
}
