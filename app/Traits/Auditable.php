<?php

namespace App\Traits;

use App\Models\Activity;

trait Auditable
{
    public function logActivity(
        string $description,
        ?string $event = null,
        array $properties = [],
        string $logName = 'default',
    ): void {
        Activity::log(
            description: $description,
            event: $event,
            subject: $this,
            causer: auth()->user(),
            properties: $properties,
            logName: $logName,
        );
    }
}

