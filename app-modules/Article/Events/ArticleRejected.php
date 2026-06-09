<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Événement ArticleRejected
 *
 * Déclenché lorsqu'un article est rejeté.
 */
class ArticleRejected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param User $reviewer
     * @param string $raison
     * @param string|null $ancienStatut
     */
    public function __construct(
        public Article $article,
        public User $reviewer,
        public string $raison,
        public ?string $ancienStatut = null
    ) {
    }
}
