@php
    /** @var \App\Modules\BillingEngine\Infrastructure\Models\BillingClient|null $client */
    $c = $client ?? null;
@endphp
<div class="space-y-8">
    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Identity') }}</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Customer code') }} *</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $c?->code ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="external_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('External / ERP ID') }}</label>
                    <input type="text" id="external_id" name="external_id" value="{{ old('external_id', $c?->external_id ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('external_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Display name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $c?->name ?? '') }}" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="legal_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Legal name') }}</label>
                    <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $c?->legal_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('legal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="trading_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Trading name') }}</label>
                    <input type="text" id="trading_name" name="trading_name" value="{{ old('trading_name', $c?->trading_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('trading_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tax_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Tax ID (VAT / GST / TIN)') }}</label>
                    <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $c?->tax_id ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('tax_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Invoice currency') }} *</label>
                    <input type="text" id="currency" name="currency" value="{{ old('currency', $c?->currency ?? 'USD') }}" maxlength="3" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Credit & terms') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="payment_terms_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Payment terms (days)') }}</label>
                <input type="number" id="payment_terms_days" name="payment_terms_days" value="{{ old('payment_terms_days', $c?->payment_terms_days ?? '') }}" min="0" max="365" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="{{ __('e.g. 30') }}">
                @error('payment_terms_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="credit_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Credit limit') }}</label>
                <input type="text" inputmode="decimal" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $c?->credit_limit !== null ? (string) $c->credit_limit : '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="0.00">
                @error('credit_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end pb-1">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="credit_hold" value="0">
                    <input type="checkbox" name="credit_hold" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked((string) old('credit_hold', ($c?->credit_hold ?? false) ? '1' : '0') === '1')>
                    {{ __('Credit hold') }}
                </label>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Bill-to address') }}</h3>
        <div class="space-y-4">
            <div>
                <label for="bill_address_line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address line 1') }}</label>
                <input type="text" id="bill_address_line1" name="bill_address_line1" value="{{ old('bill_address_line1', $c?->bill_address_line1 ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('bill_address_line1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="bill_address_line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address line 2') }}</label>
                <input type="text" id="bill_address_line2" name="bill_address_line2" value="{{ old('bill_address_line2', $c?->bill_address_line2 ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('bill_address_line2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="bill_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('City') }}</label>
                    <input type="text" id="bill_city" name="bill_city" value="{{ old('bill_city', $c?->bill_city ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('bill_city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="bill_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Region / state') }}</label>
                    <input type="text" id="bill_region" name="bill_region" value="{{ old('bill_region', $c?->bill_region ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('bill_region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="bill_postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Postal code') }}</label>
                    <input type="text" id="bill_postal_code" name="bill_postal_code" value="{{ old('bill_postal_code', $c?->bill_postal_code ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('bill_postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="bill_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Country (ISO)') }}</label>
                    <input type="text" id="bill_country" name="bill_country" value="{{ old('bill_country', $c?->bill_country ?? '') }}" maxlength="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="US">
                    @error('bill_country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Ship-to address') }}</h3>
        <div class="mb-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="ship_same_as_bill" value="0">
                <input type="checkbox" name="ship_same_as_bill" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked((string) old('ship_same_as_bill', ($c?->ship_same_as_bill ?? true) ? '1' : '0') === '1')>
                {{ __('Ship-to same as bill-to') }}
            </label>
        </div>
        <div class="space-y-4 opacity-90">
            <div>
                <label for="ship_address_line1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address line 1') }}</label>
                <input type="text" id="ship_address_line1" name="ship_address_line1" value="{{ old('ship_address_line1', $c?->ship_address_line1 ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('ship_address_line1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="ship_address_line2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Address line 2') }}</label>
                <input type="text" id="ship_address_line2" name="ship_address_line2" value="{{ old('ship_address_line2', $c?->ship_address_line2 ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('ship_address_line2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="ship_city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('City') }}</label>
                    <input type="text" id="ship_city" name="ship_city" value="{{ old('ship_city', $c?->ship_city ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('ship_city')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ship_region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Region / state') }}</label>
                    <input type="text" id="ship_region" name="ship_region" value="{{ old('ship_region', $c?->ship_region ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('ship_region')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ship_postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Postal code') }}</label>
                    <input type="text" id="ship_postal_code" name="ship_postal_code" value="{{ old('ship_postal_code', $c?->ship_postal_code ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    @error('ship_postal_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ship_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Country (ISO)') }}</label>
                    <input type="text" id="ship_country" name="ship_country" value="{{ old('ship_country', $c?->ship_country ?? '') }}" maxlength="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" placeholder="US">
                    @error('ship_country')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Invoicing & AR contact') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="invoice_contact_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact name') }}</label>
                <input type="text" id="invoice_contact_name" name="invoice_contact_name" value="{{ old('invoice_contact_name', $c?->invoice_contact_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('invoice_contact_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="invoice_contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact email') }}</label>
                <input type="email" id="invoice_contact_email" name="invoice_contact_email" value="{{ old('invoice_contact_email', $c?->invoice_contact_email ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('invoice_contact_email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="invoice_contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Contact phone') }}</label>
                <input type="text" id="invoice_contact_phone" name="invoice_contact_phone" value="{{ old('invoice_contact_phone', $c?->invoice_contact_phone ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                @error('invoice_contact_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label for="invoice_delivery_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Invoice delivery') }}</label>
                <select id="invoice_delivery_method" name="invoice_delivery_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="">{{ __('Not specified') }}</option>
                    <option value="email" @selected(old('invoice_delivery_method', $c?->invoice_delivery_method ?? '') === 'email')>{{ __('Email') }}</option>
                    <option value="portal" @selected(old('invoice_delivery_method', $c?->invoice_delivery_method ?? '') === 'portal')>{{ __('Customer portal') }}</option>
                    <option value="edi" @selected(old('invoice_delivery_method', $c?->invoice_delivery_method ?? '') === 'edi')>{{ __('EDI') }}</option>
                    <option value="mail" @selected(old('invoice_delivery_method', $c?->invoice_delivery_method ?? '') === 'mail')>{{ __('Postal mail') }}</option>
                    <option value="other" @selected(old('invoice_delivery_method', $c?->invoice_delivery_method ?? '') === 'other')>{{ __('Other') }}</option>
                </select>
                @error('invoice_delivery_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="customer_payment_method" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Typical payment method (from customer)') }}</label>
                <select id="customer_payment_method" name="customer_payment_method" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="">{{ __('Not specified') }}</option>
                    <option value="ach" @selected(old('customer_payment_method', $c?->customer_payment_method ?? '') === 'ach')>{{ __('ACH / bank transfer') }}</option>
                    <option value="wire" @selected(old('customer_payment_method', $c?->customer_payment_method ?? '') === 'wire')>{{ __('Wire') }}</option>
                    <option value="check" @selected(old('customer_payment_method', $c?->customer_payment_method ?? '') === 'check')>{{ __('Check') }}</option>
                    <option value="card" @selected(old('customer_payment_method', $c?->customer_payment_method ?? '') === 'card')>{{ __('Card') }}</option>
                    <option value="other" @selected(old('customer_payment_method', $c?->customer_payment_method ?? '') === 'other')>{{ __('Other') }}</option>
                </select>
                @error('customer_payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="mt-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="po_number_required" value="0">
                <input type="checkbox" name="po_number_required" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked((string) old('po_number_required', ($c?->po_number_required ?? false) ? '1' : '0') === '1')>
                {{ __('Customer requires PO number on invoices') }}
            </label>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Accounting') }}</h3>
        <div>
            <label for="default_revenue_account_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Default revenue / GL account code') }}</label>
            <input type="text" id="default_revenue_account_code" name="default_revenue_account_code" value="{{ old('default_revenue_account_code', $c?->default_revenue_account_code ?? '') }}" class="mt-1 block w-full max-w-md rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            @error('default_revenue_account_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 border-b border-gray-200 dark:border-gray-600 pb-2 mb-4">{{ __('Internal') }}</h3>
        <div>
            <label for="internal_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Internal notes (credit / collections)') }}</label>
            <textarea id="internal_notes" name="internal_notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('internal_notes', $c?->internal_notes ?? '') }}</textarea>
            @error('internal_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="mt-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600" @checked((string) old('is_active', ($c?->is_active ?? true) ? '1' : '0') === '1')>
                {{ __('Active client') }}
            </label>
        </div>
    </div>
</div>
