<?php

namespace App\Modules\Treasury\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'currency',
        'gl_account_code',
        'opening_balance',
        'opened_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'opened_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class, 'bank_account_id');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'bank_account_id');
    }

    public function getBalanceAttribute(): float
    {
        $sum = isset($this->attributes['transactions_sum_amount'])
            ? (float) $this->attributes['transactions_sum_amount']
            : (float) $this->transactions()->sum('amount');
        return (float) $this->opening_balance + $sum;
    }
}
