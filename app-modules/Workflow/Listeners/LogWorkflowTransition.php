<?php

declare(strict_types=1);

namespace Modules\Workflow\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Workflow\Events\WorkflowTransitionExecuted;

/**
 * Enregistre les transitions de workflow dans les logs
 */
class LogWorkflowTransition
{
    public function handle(WorkflowTransitionExecuted $event): void
    {
        if (!config('workflow.logging.log_all_transitions', true)) {
            return;
        }

        Log::channel('workflow')->info('Transition de workflow exécutée', [
            'transition_id' => $event->transition->id,
            'article_id' => $event->article->id,
            'from' => $event->transition->statut_origine,
            'to' => $event->transition->statut_cible,
            'user_id' => $event->transition->utilisateur_id,
            'ip' => $event->transition->ip_address,
        ]);
    }
}
