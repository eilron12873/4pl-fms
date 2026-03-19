<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostingSavedFilter extends Model
{
    protected $table = 'costing_saved_filters';

    protected $fillable = [
        'user_id',
        'report_key',
        'name',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

