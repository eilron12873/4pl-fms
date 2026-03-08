<?php

namespace App\Core\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public const LOG_FINANCIAL = 'financial';
    public const LOG_AUDIT = 'audit';
    public const LOG_DEFAULT = 'default';

    /**
     * Log an audit entry. Use for financial posting, period close, and other governance events.
     */
    public function log(
        string $description,
        ?string $event = null,
        ?Model $subject = null,
        array $properties = [],
        string $logName = self::LOG_DEFAULT,
    ): Activity {
        return Activity::log(
            description: $description,
            event: $event,
            subject: $subject,
            causer: Auth::user(),
            properties: array_merge($properties, [
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]),
            logName: $logName,
        );
    }

    /**
     * Log a financial event (e.g. journal posted, period closed).
     */
    public function logFinancial(string $description, ?Model $subject = null, array $properties = [], ?string $event = null): Activity
    {
        return $this->log($description, $event ?? 'financial', $subject, $properties, self::LOG_FINANCIAL);
    }
}
