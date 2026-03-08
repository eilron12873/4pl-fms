<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'service_type_id');
    }

    public static function rateTypeForServiceCode(string $code): ?string
    {
        $map = [
            'freight' => 'per_trip',
            'storage' => 'per_pallet_day',
            'handling' => 'per_trip',
            'courier' => 'per_trip',
            'project_milestone' => 'fixed',
        ];

        return $map[$code] ?? 'per_trip';
    }
}
