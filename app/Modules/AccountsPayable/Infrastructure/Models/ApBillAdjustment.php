<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApBillAdjustment extends Model
{
    protected $table = 'ap_bill_adjustments';

    protected $fillable = [
        'bill_id',
        'type',
        'adjustment_number',
        'amount',
        'reason',
        'journal_id',
        'adjustment_date',
        'status',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(ApBill::class, 'bill_id');
    }
}
