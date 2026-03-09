<?php

namespace App\Modules\CoreAccounting\Infrastructure\Repositories;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Database\Eloquent\Collection;

class AccountRepository
{
    public function findByCode(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    public function findOrCreateByCode(string $code, string $name, string $type, int $level = 2, bool $isPosting = true): Account
    {
        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'level' => $level,
                'is_posting' => $isPosting,
            ],
        );
    }

    /**
     * @return Collection<int, Account>
     */
    public function allPosting(): Collection
    {
        return Account::where('is_posting', true)
            ->orderBy('code')
            ->get();
    }
}

