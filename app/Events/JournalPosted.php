<?php

namespace App\Events;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JournalPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Journal $journal,
        public array $meta = [],
    ) {
    }
}
