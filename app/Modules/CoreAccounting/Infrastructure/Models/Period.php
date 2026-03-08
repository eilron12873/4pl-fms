<?php

namespace App\Modules\CoreAccounting\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'code',
        'start_date',
        'end_date',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if a given date falls within this period.
     */
    public function containsDate(string $date): bool
    {
        $d = \Carbon\Carbon::parse($date)->startOfDay();

        return $d->between($this->start_date->startOfDay(), $this->end_date->endOfDay());
    }

    /**
     * Scope: open periods only.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
