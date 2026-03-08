<?php

namespace App\Modules\CoreAccounting\Domain\Exceptions;

use RuntimeException;

class JournalImmutableException extends RuntimeException
{
    public function __construct(
        string $message = 'Posted journals are immutable. Use reversal to correct entries.',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
