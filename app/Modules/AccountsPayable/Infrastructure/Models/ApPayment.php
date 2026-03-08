<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'payment_method',
        'bank_account_id',
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

    public function voucher(): HasOne
    {
        return $this->hasOne(ApVoucher::class, 'payment_id');
    }

    public function check(): HasOne
    {
        return $this->hasOne(ApCheck::class, 'payment_id');
    }
}
