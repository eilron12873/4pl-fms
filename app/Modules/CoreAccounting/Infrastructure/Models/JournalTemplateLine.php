<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalTemplateLine extends Model
{
    protected $fillable = [
        'journal_template_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'client_id',
        'shipment_id',
        'route_id',
        'warehouse_id',
        'vehicle_id',
        'project_id',
        'service_line_id',
        'cost_center_id',
        'sequence',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(JournalTemplate::class, 'journal_template_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

