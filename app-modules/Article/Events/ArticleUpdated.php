<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;

/**
 * Événement ArticleUpdated
 *
 * Déclenché lorsqu'un article est mis à jour.
 */
class ArticleUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     * @param array $changedAttributes
     */
    public function __construct(
        public Article $article,
        public array $changedAttributes = []
    ) {
    }
}
