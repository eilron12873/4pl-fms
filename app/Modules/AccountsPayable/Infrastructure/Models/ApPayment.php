<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApPayment extends Model
{
    protected $table = 'ap_payments';

    protected $fillable = [
        'vendor_id',
        'payment_date',
        'amount',
        'currency',
        'reference',
        'notes',
        'journal_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function billPayments(): HasMany
    {
        return $this->hasMany(ApBillPayment::class, 'payment_id');
    }
}
