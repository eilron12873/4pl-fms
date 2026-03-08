<?php

namespace App\Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'purchase_order_id',
        'purchase_request_line_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'account_code',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseRequestLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestLine::class, 'purchase_request_line_id');
    }
}
