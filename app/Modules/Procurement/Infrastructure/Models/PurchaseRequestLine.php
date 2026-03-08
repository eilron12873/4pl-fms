<?php

namespace App\Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestLine extends Model
{
    protected $table = 'purchase_request_lines';

    protected $fillable = [
        'purchase_request_id',
        'description',
        'quantity',
        'estimated_unit_cost',
        'account_code',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'estimated_unit_cost' => 'decimal:4',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }
}
