<?php

namespace App\Modules\CoreAccounting\Infrastructure\Repositories;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Database\Eloquent\Collection;

class JournalRepository
{
    /**
     * @return Collection<int, Journal>
     */
    public function latest(int $limit = 50): Collection
    {
        return Journal::orderByDesc('journal_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}

