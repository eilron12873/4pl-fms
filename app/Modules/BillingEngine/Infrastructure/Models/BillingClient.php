<?php

namespace App\Modules\BillingEngine\Infrastructure\Models;

use App\Modules\AccountsReceivable\Infrastructure\Models\ArInvoice;
use App\Modules\AccountsReceivable\Infrastructure\Models\ArPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\Intl\Currencies;

class BillingClient extends Model
{
    protected $appends = ['display_name'];

    protected $fillable = [
        'external_id',
        'code',
        'name',
        'legal_name',
        'trading_name',
        'tax_id',
        'currency',
        'payment_terms_days',
        'credit_limit',
        'credit_hold',
        'bill_address_line1',
        'bill_address_line2',
        'bill_city',
        'bill_region',
        'bill_postal_code',
        'bill_country',
        'ship_same_as_bill',
        'ship_address_line1',
        'ship_address_line2',
        'ship_city',
        'ship_region',
        'ship_postal_code',
        'ship_country',
        'invoice_contact_name',
        'invoice_contact_email',
        'invoice_contact_phone',
        'invoice_delivery_method',
        'customer_payment_method',
        'po_number_required',
        'default_revenue_account_code',
        'internal_notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_hold' => 'boolean',
        'ship_same_as_bill' => 'boolean',
        'po_number_required' => 'boolean',
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Validation rules for create/update forms (AR + Billing Engine).
     *
     * @return array<string, mixed>
     */
    public static function formValidationRules(?int $ignoreClientId = null): array
    {
        $codeRule = Rule::unique('billing_clients', 'code');
        if ($ignoreClientId !== null) {
            $codeRule->ignore($ignoreClientId);
        }

        $currencyCodes = self::intlCurrencyCodes();

        return [
            'code' => ['required', 'string', 'max:50', $codeRule],
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'currency' => ['required', 'string', 'size:3', Rule::in($currencyCodes)],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'bill_address_line1' => ['nullable', 'string', 'max:255'],
            'bill_address_line2' => ['nullable', 'string', 'max:255'],
            'bill_city' => ['nullable', 'string', 'max:128'],
            'bill_region' => ['nullable', 'string', 'max:128'],
            'bill_postal_code' => ['nullable', 'string', 'max:32'],
            'bill_country' => ['nullable', 'string', 'size:2'],
            'ship_address_line1' => ['nullable', 'string', 'max:255'],
            'ship_address_line2' => ['nullable', 'string', 'max:255'],
            'ship_city' => ['nullable', 'string', 'max:128'],
            'ship_region' => ['nullable', 'string', 'max:128'],
            'ship_postal_code' => ['nullable', 'string', 'max:32'],
            'ship_country' => ['nullable', 'string', 'size:2'],
            'invoice_contact_name' => ['nullable', 'string', 'max:255'],
            'invoice_contact_email' => ['nullable', 'email', 'max:255'],
            'invoice_contact_phone' => ['nullable', 'string', 'max:64'],
            'invoice_delivery_method' => ['nullable', 'string', 'in:email,portal,edi,mail,other'],
            'customer_payment_method' => ['nullable', 'string', 'in:ach,wire,check,card,other'],
            'default_revenue_account_code' => ['nullable', 'string', 'max:32'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Validated + normalized attributes from a client form (excludes is_active; set that in the controller).
     *
     * @return array<string, mixed>
     */
    public static function prepareValidatedPayload(Request $request, ?int $ignoreClientId = null): array
    {
        if ($request->has('currency')) {
            $request->merge([
                'currency' => strtoupper(trim((string) $request->input('currency'))),
            ]);
        }
        $data = $request->validate(self::formValidationRules($ignoreClientId));
        $data = self::normalizeEmptyStringsToNull($data);
        $data['currency'] = strtoupper($data['currency']);
        foreach (['bill_country', 'ship_country'] as $k) {
            if (! empty($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        $data['credit_hold'] = $request->boolean('credit_hold');
        $data['ship_same_as_bill'] = $request->boolean('ship_same_as_bill');
        $data['po_number_required'] = $request->boolean('po_number_required');
        if ($data['ship_same_as_bill']) {
            foreach (['ship_address_line1', 'ship_address_line2', 'ship_city', 'ship_region', 'ship_postal_code', 'ship_country'] as $k) {
                $data[$k] = null;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeEmptyStringsToNull(array $data): array
    {
        $nullable = [
            'legal_name', 'trading_name', 'external_id', 'tax_id',
            'payment_terms_days', 'credit_limit',
            'bill_address_line1', 'bill_address_line2', 'bill_city', 'bill_region', 'bill_postal_code', 'bill_country',
            'ship_address_line1', 'ship_address_line2', 'ship_city', 'ship_region', 'ship_postal_code', 'ship_country',
            'invoice_contact_name', 'invoice_contact_email', 'invoice_contact_phone',
            'invoice_delivery_method', 'customer_payment_method', 'default_revenue_account_code', 'internal_notes',
        ];
        foreach ($nullable as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    /**
     * ISO 4217 codes from Symfony Intl (same source as company settings / procurement).
     *
     * @return list<string>
     */
    public static function intlCurrencyCodes(): array
    {
        return array_keys(Currencies::getNames());
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'client_id');
    }

    public function arInvoices(): HasMany
    {
        return $this->hasMany(ArInvoice::class, 'client_id');
    }

    public function arPayments(): HasMany
    {
        return $this->hasMany(ArPayment::class, 'client_id');
    }

    /**
     * Display name for invoices (trading name preferred when set).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?: $this->name;
    }
}
