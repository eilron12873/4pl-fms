<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApVoucher extends Model
{
    protected $table = 'ap_vouchers';

    protected $fillable = [
        'voucher_number',
        'payment_id',
        'voucher_date',
    ];

    protected $casts = [
        'voucher_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ApPayment::class, 'payment_id');
    }
}
