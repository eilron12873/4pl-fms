<?php

namespace App\Modules\AccountsReceivable\Infrastructure\Models;

use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArPayment extends Model
{
    protected $table = 'ar_payments';

    protected $fillable = [
        'client_id',
        'payment_date',
        'amount',
        'currency',
        'reference',
        'notes',
        'journal_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(BillingClient::class, 'client_id');
    }

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(ArInvoicePayment::class, 'payment_id');
    }
}
