<?php

declare(strict_types=1);

namespace Modules\Article\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;

/**
 * Événement ArticleCreated
 *
 * Déclenché lorsqu'un nouvel article est créé.
 */
class ArticleCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param Article $article
     */
    public function __construct(public Article $article)
    {
    }
}
