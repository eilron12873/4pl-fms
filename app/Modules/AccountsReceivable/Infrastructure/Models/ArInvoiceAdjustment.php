<?php

namespace App\Modules\AccountsReceivable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArInvoiceAdjustment extends Model
{
    protected $table = 'ar_invoice_adjustments';

    protected $fillable = [
        'invoice_id',
        'type',
        'adjustment_number',
        'amount',
        'reason',
        'journal_id',
        'adjustment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'adjustment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'invoice_id');
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }
}
