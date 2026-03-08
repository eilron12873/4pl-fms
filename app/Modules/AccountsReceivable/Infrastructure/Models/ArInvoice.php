<?php

namespace App\Modules\AccountsReceivable\Infrastructure\Models;

use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArInvoice extends Model
{
    protected $table = 'ar_invoices';

    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'total',
        'amount_allocated',
        'currency',
        'notes',
        'journal_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_allocated' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(BillingClient::class, 'client_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ArInvoiceLine::class, 'invoice_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ArInvoiceAdjustment::class, 'invoice_id');
    }

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(ArInvoicePayment::class, 'invoice_id');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_allocated;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isIssued(): bool
    {
        return in_array($this->status, ['issued', 'partially_paid', 'paid'], true);
    }
}
