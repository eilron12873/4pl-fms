<?php

namespace App\Modules\Treasury\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $table = 'bank_statement_lines';

    protected $fillable = [
        'bank_account_id',
        'statement_date',
        'description',
        'amount',
        'reference',
        'bank_sequence',
        'bank_transaction_id',
        'matched_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'amount' => 'decimal:2',
        'matched_at' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    public function isMatched(): bool
    {
        return $this->bank_transaction_id !== null;
    }
}
