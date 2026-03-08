<?php

namespace App\Modules\CoreAccounting\Domain\Exceptions;

use RuntimeException;

class PeriodLockedException extends RuntimeException
{
    public function __construct(
        string $message = 'Posting is not allowed: period is closed or date does not fall in an open period.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
