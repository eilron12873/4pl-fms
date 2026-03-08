<?php

namespace App\Modules\Procurement\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';

    protected $table = 'purchase_requests';

    protected $fillable = [
        'pr_number',
        'requested_by',
        'department',
        'request_date',
        'status',
        'approval_date',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approval_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequestLine::class, 'purchase_request_id');
    }
}
