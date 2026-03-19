<?php

namespace App\Modules\CostingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class CostingEngineSetting extends Model
{
    protected $table = 'costing_engine_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    protected $casts = [
        'setting_value' => 'array',
    ];
}

