<?php

declare(strict_types=1);

namespace Modules\Security\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Security\Models\AuditLog;

class SensitiveActionPerformed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AuditLog $auditLog
    ) {}
}
