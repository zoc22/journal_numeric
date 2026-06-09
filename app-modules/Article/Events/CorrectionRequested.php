<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Événement CorrectionRequested
 *
 * Déclenché lorsqu'un reviewer demande des corrections sur un article.
 */
class CorrectionRequested
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param User $reviewer
     * @param string $commentaires
     * @param array|null $annotations
     */
    public function __construct(
        public Article $article,
        public User $reviewer,
        public string $commentaires,
        public ?array $annotations = null
    ) {
    }
}
