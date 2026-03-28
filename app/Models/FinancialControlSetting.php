<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialControlSetting extends Model
{
    protected $table = 'financial_control_settings';

    protected $fillable = [
        'max_backdating_days',
        'allow_manual_journals',
        'thresholds',
    ];

    protected $casts = [
        'allow_manual_journals' => 'boolean',
        'thresholds' => 'array',
    ];
}
