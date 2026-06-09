<?php

declare(strict_types=1);

namespace Modules\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Article\Models\Article;
use Modules\Workflow\Models\WorkflowTransition;

class WorkflowTransitionExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkflowTransition $transition,
        public Article $article
    ) {}
}
