<?php

namespace App\Modules\AccountsPayable\Infrastructure\Models;

use App\Modules\Procurement\Infrastructure\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'code',
        'name',
        'category',
        'tax_id',
        'currency',
        'payment_terms_days',
        'is_active',
        'notes',
        'bank_name',
        'bank_account_number',
        'bank_swift_code',
        'preferred_payment_method',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(ApBill::class, 'vendor_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ApPayment::class, 'vendor_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }
}
