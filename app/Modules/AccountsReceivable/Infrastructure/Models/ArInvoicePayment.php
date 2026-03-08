<?php

namespace App\Modules\AccountsReceivable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArInvoicePayment extends Model
{
    protected $table = 'ar_invoice_payments';

    protected $fillable = [
        'invoice_id',
        'payment_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'invoice_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ArPayment::class, 'payment_id');
    }
}
