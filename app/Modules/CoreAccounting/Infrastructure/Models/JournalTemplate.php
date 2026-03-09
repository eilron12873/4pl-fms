<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_recurring',
        'frequency',
        'next_run_at',
        'is_active',
    ];

    protected $casts = [
        'is_recurring' => 'bool',
        'is_active' => 'bool',
        'next_run_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalTemplateLine::class)->orderBy('sequence');
    }
}

