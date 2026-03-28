<?php

namespace App\Models;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_inclusive',
        'rounding_mode',
        'input_account_id',
        'output_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_inclusive' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function inputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'input_account_id');
    }

    public function outputAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'output_account_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class)->orderByDesc('effective_from');
    }
}
