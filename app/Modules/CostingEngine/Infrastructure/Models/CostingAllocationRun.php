<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class CostingAllocationRun extends Model
{
    protected $table = 'costing_allocation_runs';

    protected $fillable = [
        'run_date',
        'requested_by',
        'requested_at',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'comments',
        'metadata',
    ];

    protected $casts = [
        'run_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}

