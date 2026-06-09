<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Événement ArticleSubmitted
 *
 * Déclenché lorsqu'un article est soumis pour relecture.
 */
class ArticleSubmitted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param User $journaliste
     * @param string|null $note
     * @param string|null $ancienStatut
     */
    public function __construct(
        public Article $article,
        public User $journaliste,
        public ?string $note = null,
        public ?string $ancienStatut = null
    ) {
    }
}
