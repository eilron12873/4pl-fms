<?php

namespace App\Modules\Treasury\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankTransaction extends Model
{
    protected $table = 'bank_transactions';

    protected $fillable = [
        'bank_account_id',
        'transaction_date',
        'description',
        'amount',
        'reference',
        'type',
        'source_type',
        'source_id',
        'reconciled_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'reconciled_at' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function statementLine(): HasOne
    {
        return $this->hasOne(BankStatementLine::class, 'bank_transaction_id');
    }

    public function isReconciled(): bool
    {
        return $this->reconciled_at !== null;
    }
}
