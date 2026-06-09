<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Événement ArticleApproved
 *
 * Déclenché lorsqu'un article est approuvé (à n'importe quel niveau).
 */
class ArticleApproved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param User $validateur
     * @param string $niveauValidation
     * @param string|null $commentaire
     */
    public function __construct(
        public Article $article,
        public User $validateur,
        public string $niveauValidation,
        public ?string $commentaire = null
    ) {
    }
}
