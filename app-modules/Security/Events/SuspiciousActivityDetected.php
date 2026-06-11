<?php

declare(strict_types=1);

namespace Modules\Security\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousActivityDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $type,
        public array $data
    ) {}
}
