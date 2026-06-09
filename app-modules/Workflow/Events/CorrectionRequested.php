<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

class CorrectionRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Article $article,
        public User $reviewer,
        public ?string $commentaire = null
    ) {}
}
