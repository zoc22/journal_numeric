<?php

declare(strict_types=1);

namespace Modules\Recruitment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Recruitment\Models\Application;
use Modules\User\Models\User;

class ApplicationSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Application $application,
        public User $candidat
    ) {}
}
