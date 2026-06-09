<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Événement ArticleArchived
 *
 * Déclenché lorsqu'un article est archivé.
 */
class ArticleArchived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param User $utilisateur
     * @param string|null $ancienStatut
     */
    public function __construct(
        public Article $article,
        public User $utilisateur,
        public ?string $ancienStatut = null
    ) {
    }
}
