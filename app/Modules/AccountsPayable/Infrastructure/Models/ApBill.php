<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApBill extends Model
{
    protected $table = 'ap_bills';

    protected $fillable = [
        'vendor_id',
        'purchase_order_id',
        'bill_number',
        'bill_date',
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
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_allocated' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ApBillLine::class, 'bill_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ApBillAdjustment::class, 'bill_id');
    }

    public function billPayments(): HasMany
    {
        return $this->hasMany(ApBillPayment::class, 'bill_id');
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
