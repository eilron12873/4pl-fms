<?php

namespace App\Modules\AccountsReceivable\Infrastructure\Models;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArInvoiceLine extends Model
{
    protected $table = 'ar_invoice_lines';

    protected $fillable = [
        'invoice_id',
        'journal_id',
        'source_type',
        'source_reference',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'shipment_id',
        'client_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'invoice_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }
}
