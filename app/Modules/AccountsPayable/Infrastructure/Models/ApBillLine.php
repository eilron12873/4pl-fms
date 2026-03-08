<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApBillLine extends Model
{
    protected $table = 'ap_bill_lines';

    protected $fillable = [
        'bill_id', 'journal_id', 'source_type', 'source_reference', 'description',
        'quantity', 'unit_price', 'amount', 'vendor_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(ApBill::class, 'bill_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
