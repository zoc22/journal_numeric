<?php

declare(strict_types=1);

namespace Modules\Recruitment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Recruitment\Models\CallForApplication;
use Modules\User\Models\User;

class CallPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CallForApplication $call,
        public User $editeur
    ) {}
}
