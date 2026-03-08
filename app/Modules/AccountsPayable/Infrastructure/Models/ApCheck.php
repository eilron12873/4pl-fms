<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use App\Modules\Treasury\Infrastructure\Models\BankAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApCheck extends Model
{
    protected $table = 'ap_checks';

    public const STATUS_PRINTED = 'printed';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'check_number',
        'payment_id',
        'bank_account_id',
        'check_date',
        'amount',
        'payee',
        'status',
    ];

    protected $casts = [
        'check_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ApPayment::class, 'payment_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
