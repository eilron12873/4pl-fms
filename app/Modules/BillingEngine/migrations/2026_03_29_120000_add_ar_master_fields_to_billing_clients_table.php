<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_clients', function (Blueprint $table) {
            $table->string('legal_name', 255)->nullable()->after('name');
            $table->string('trading_name', 255)->nullable()->after('legal_name');
            $table->string('tax_id', 64)->nullable()->after('trading_name');
            $table->unsignedSmallInteger('payment_terms_days')->nullable()->after('currency');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('payment_terms_days');
            $table->boolean('credit_hold')->default(false)->after('credit_limit');
            $table->string('bill_address_line1', 255)->nullable()->after('credit_hold');
            $table->string('bill_address_line2', 255)->nullable()->after('bill_address_line1');
            $table->string('bill_city', 128)->nullable()->after('bill_address_line2');
            $table->string('bill_region', 128)->nullable()->after('bill_city');
            $table->string('bill_postal_code', 32)->nullable()->after('bill_region');
            $table->string('bill_country', 2)->nullable()->after('bill_postal_code');
            $table->boolean('ship_same_as_bill')->default(true)->after('bill_country');
            $table->string('ship_address_line1', 255)->nullable()->after('ship_same_as_bill');
            $table->string('ship_address_line2', 255)->nullable()->after('ship_address_line1');
            $table->string('ship_city', 128)->nullable()->after('ship_address_line2');
            $table->string('ship_region', 128)->nullable()->after('ship_city');
            $table->string('ship_postal_code', 32)->nullable()->after('ship_region');
            $table->string('ship_country', 2)->nullable()->after('ship_postal_code');
            $table->string('invoice_contact_name', 255)->nullable()->after('ship_country');
            $table->string('invoice_contact_email', 255)->nullable()->after('invoice_contact_name');
            $table->string('invoice_contact_phone', 64)->nullable()->after('invoice_contact_email');
            $table->string('invoice_delivery_method', 20)->nullable()->after('invoice_contact_phone');
            $table->string('customer_payment_method', 20)->nullable()->after('invoice_delivery_method');
            $table->boolean('po_number_required')->default(false)->after('customer_payment_method');
            $table->string('default_revenue_account_code', 32)->nullable()->after('po_number_required');
            $table->text('internal_notes')->nullable()->after('default_revenue_account_code');
        });
    }

    public function down(): void
    {
        Schema::table('billing_clients', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'trading_name',
                'tax_id',
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
            ]);
        });
    }
};
