<?php

namespace Database\Seeders;

use App\Modules\BillingEngine\Infrastructure\Models\BillingClient;
use Illuminate\Database\Seeder;

/**
 * Populates billing_clients with realistic AR master data for UI testing (AR, Billing Engine, pickers, rate simulation).
 * Run before AccountsReceivableDemoSeeder so demo invoices attach to these rows.
 */
class BillingClientsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedArDemoClients();
        $this->seedExtraUiClients();
        $this->seedCostingDemoClientDetails();
    }

    private function seedArDemoClients(): void
    {
        BillingClient::updateOrCreate(
            ['code' => 'AR-DEMO'],
            [
                'name' => 'Demo Client Holdings Inc.',
                'legal_name' => 'Demo Client Holdings Incorporated',
                'trading_name' => 'AR Demo Client',
                'external_id' => 'ERP-CUST-90001',
                'tax_id' => '12-3456789',
                'currency' => 'USD',
                'payment_terms_days' => 30,
                'credit_limit' => 50000.00,
                'credit_hold' => false,
                'bill_address_line1' => '100 Commerce Parkway',
                'bill_address_line2' => 'Suite 200',
                'bill_city' => 'Dallas',
                'bill_region' => 'TX',
                'bill_postal_code' => '75201',
                'bill_country' => 'US',
                'ship_same_as_bill' => true,
                'ship_address_line1' => null,
                'ship_address_line2' => null,
                'ship_city' => null,
                'ship_region' => null,
                'ship_postal_code' => null,
                'ship_country' => null,
                'invoice_contact_name' => 'Accounts Payable',
                'invoice_contact_email' => 'ap@ardemoclient.example.test',
                'invoice_contact_phone' => '+1-555-0100',
                'invoice_delivery_method' => 'email',
                'customer_payment_method' => 'ach',
                'po_number_required' => false,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'Net 30. Primary AP mailbox for invoices.',
                'is_active' => true,
            ],
        );

        BillingClient::updateOrCreate(
            ['code' => 'AR-WAREHOUSE'],
            [
                'name' => 'Warehouse Client LLC',
                'legal_name' => 'Warehouse Client Logistics LLC',
                'trading_name' => 'WH Client',
                'external_id' => 'ERP-CUST-90002',
                'tax_id' => '98-7654321',
                'currency' => 'USD',
                'payment_terms_days' => 45,
                'credit_limit' => 125000.00,
                'credit_hold' => false,
                'bill_address_line1' => '200 Billing Boulevard',
                'bill_city' => 'Chicago',
                'bill_region' => 'IL',
                'bill_postal_code' => '60601',
                'bill_country' => 'US',
                'ship_same_as_bill' => false,
                'ship_address_line1' => '500 Distribution Way',
                'ship_address_line2' => 'Dock 12',
                'ship_city' => 'Memphis',
                'ship_region' => 'TN',
                'ship_postal_code' => '38118',
                'ship_country' => 'US',
                'invoice_contact_name' => 'Finance Ops',
                'invoice_contact_email' => 'finance@whclient.example.test',
                'invoice_contact_phone' => '+1-555-0200',
                'invoice_delivery_method' => 'portal',
                'customer_payment_method' => 'wire',
                'po_number_required' => true,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'PO mandatory on every invoice. Ship-to is Memphis DC.',
                'is_active' => true,
            ],
        );

        BillingClient::updateOrCreate(
            ['code' => 'AR-INTL'],
            [
                'name' => 'International Client GmbH',
                'legal_name' => 'International Client Gesellschaft mit beschränkter Haftung',
                'trading_name' => 'Intl Client EU',
                'external_id' => 'SAP-DE-44001',
                'tax_id' => 'DE123456789',
                'currency' => 'EUR',
                'payment_terms_days' => 14,
                'credit_limit' => 75000.00,
                'credit_hold' => false,
                'bill_address_line1' => 'Hafenstraße 12',
                'bill_city' => 'Hamburg',
                'bill_region' => 'HH',
                'bill_postal_code' => '20457',
                'bill_country' => 'DE',
                'ship_same_as_bill' => true,
                'ship_address_line1' => null,
                'ship_address_line2' => null,
                'ship_city' => null,
                'ship_region' => null,
                'ship_postal_code' => null,
                'ship_country' => null,
                'invoice_contact_name' => 'Buchhaltung',
                'invoice_contact_email' => 'rechnungen@intlclient.example.test',
                'invoice_contact_phone' => '+49-40-555010',
                'invoice_delivery_method' => 'edi',
                'customer_payment_method' => 'wire',
                'po_number_required' => false,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'EDI 810/850 enabled. VAT ID on file.',
                'is_active' => true,
            ],
        );
    }

    private function seedExtraUiClients(): void
    {
        BillingClient::updateOrCreate(
            ['code' => 'AR-CAD-RETAIL'],
            [
                'name' => 'Maple Retail Cooperative',
                'legal_name' => 'Maple Retail Cooperative Association',
                'trading_name' => 'Maple Retail',
                'external_id' => 'NAV-CA-7788',
                'tax_id' => '123456789RT0001',
                'currency' => 'CAD',
                'payment_terms_days' => 30,
                'credit_limit' => 25000.00,
                'credit_hold' => false,
                'bill_address_line1' => '88 Queen Street West',
                'bill_city' => 'Toronto',
                'bill_region' => 'ON',
                'bill_postal_code' => 'M5H 2M9',
                'bill_country' => 'CA',
                'ship_same_as_bill' => true,
                'ship_address_line1' => null,
                'ship_address_line2' => null,
                'ship_city' => null,
                'ship_region' => null,
                'ship_postal_code' => null,
                'ship_country' => null,
                'invoice_contact_name' => 'AP — Maple Retail',
                'invoice_contact_email' => 'ap@mapleretail.example.test',
                'invoice_contact_phone' => '+1-416-555-0300',
                'invoice_delivery_method' => 'mail',
                'customer_payment_method' => 'check',
                'po_number_required' => true,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'Canadian GST. Mailed invoice copy required.',
                'is_active' => true,
            ],
        );

        BillingClient::updateOrCreate(
            ['code' => 'AR-CREDIT-HOLD'],
            [
                'name' => 'Summit Parts Inc.',
                'legal_name' => 'Summit Parts Incorporated',
                'trading_name' => null,
                'external_id' => 'ERP-CUST-91000',
                'tax_id' => '55-1122334',
                'currency' => 'USD',
                'payment_terms_days' => 0,
                'credit_limit' => 5000.00,
                'credit_hold' => true,
                'bill_address_line1' => '9 Industrial Road',
                'bill_city' => 'Phoenix',
                'bill_region' => 'AZ',
                'bill_postal_code' => '85001',
                'bill_country' => 'US',
                'ship_same_as_bill' => true,
                'ship_address_line1' => null,
                'ship_address_line2' => null,
                'ship_city' => null,
                'ship_region' => null,
                'ship_postal_code' => null,
                'ship_country' => null,
                'invoice_contact_name' => 'Collections liaison',
                'invoice_contact_email' => 'collections@summitparts.example.test',
                'invoice_contact_phone' => '+1-555-0400',
                'invoice_delivery_method' => 'email',
                'customer_payment_method' => 'card',
                'po_number_required' => false,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'Credit hold — collections review before new orders.',
                'is_active' => true,
            ],
        );

        BillingClient::updateOrCreate(
            ['code' => 'AR-INACTIVE'],
            [
                'name' => 'Legacy Freight Partners',
                'legal_name' => 'Legacy Freight Partners Ltd.',
                'trading_name' => 'Legacy Freight',
                'external_id' => 'LEGACY-001',
                'tax_id' => null,
                'currency' => 'USD',
                'payment_terms_days' => null,
                'credit_limit' => null,
                'credit_hold' => false,
                'bill_address_line1' => '1 Old Wharf',
                'bill_city' => 'Boston',
                'bill_region' => 'MA',
                'bill_postal_code' => '02110',
                'bill_country' => 'US',
                'ship_same_as_bill' => true,
                'ship_address_line1' => null,
                'ship_address_line2' => null,
                'ship_city' => null,
                'ship_region' => null,
                'ship_postal_code' => null,
                'ship_country' => null,
                'invoice_contact_name' => null,
                'invoice_contact_email' => null,
                'invoice_contact_phone' => null,
                'invoice_delivery_method' => null,
                'customer_payment_method' => null,
                'po_number_required' => false,
                'default_revenue_account_code' => null,
                'internal_notes' => 'Inactive account — historical reference only.',
                'is_active' => false,
            ],
        );

        BillingClient::updateOrCreate(
            ['code' => 'AR-OTHER'],
            [
                'name' => 'Pacific Co-Pack Services',
                'legal_name' => null,
                'trading_name' => 'Pacific Co-Pack',
                'external_id' => null,
                'tax_id' => null,
                'currency' => 'USD',
                'payment_terms_days' => 60,
                'credit_limit' => 200000.00,
                'credit_hold' => false,
                'bill_address_line1' => '4000 Bayfront Drive',
                'bill_address_line2' => null,
                'bill_city' => 'Oakland',
                'bill_region' => 'CA',
                'bill_postal_code' => '94607',
                'bill_country' => 'US',
                'ship_same_as_bill' => false,
                'ship_address_line1' => 'Port Logistics Annex',
                'ship_address_line2' => 'Building C',
                'ship_city' => 'Long Beach',
                'ship_region' => 'CA',
                'ship_postal_code' => '90802',
                'ship_country' => 'US',
                'invoice_contact_name' => 'Vendor management',
                'invoice_contact_email' => 'vm@pacificcopack.example.test',
                'invoice_contact_phone' => '+1-555-0500',
                'invoice_delivery_method' => 'other',
                'customer_payment_method' => 'other',
                'po_number_required' => false,
                'default_revenue_account_code' => '423000',
                'internal_notes' => 'Uses "other" delivery / payment flags for UI coverage.',
                'is_active' => true,
            ],
        );
    }

    private function seedCostingDemoClientDetails(): void
    {
        BillingClient::where('code', 'DEMO-A')->update([
            'legal_name' => 'Demo Client A Corporation',
            'trading_name' => 'Demo Client A',
            'external_id' => 'COST-DEMO-A',
            'tax_id' => '11-1111111',
            'payment_terms_days' => 30,
            'credit_limit' => 100000.00,
            'bill_address_line1' => '1 Demo Plaza',
            'bill_city' => 'Atlanta',
            'bill_region' => 'GA',
            'bill_postal_code' => '30303',
            'bill_country' => 'US',
            'ship_same_as_bill' => true,
            'invoice_delivery_method' => 'email',
            'customer_payment_method' => 'ach',
        ]);

        BillingClient::where('code', 'DEMO-B')->update([
            'legal_name' => 'Demo Client B Holdings',
            'trading_name' => null,
            'external_id' => 'COST-DEMO-B',
            'payment_terms_days' => 45,
            'credit_limit' => 80000.00,
            'bill_address_line1' => '2 Sample Street',
            'bill_city' => 'Denver',
            'bill_region' => 'CO',
            'bill_postal_code' => '80202',
            'bill_country' => 'US',
            'ship_same_as_bill' => true,
            'invoice_delivery_method' => 'portal',
            'customer_payment_method' => 'wire',
        ]);

        BillingClient::where('code', 'DEMO-EU')->update([
            'legal_name' => 'Demo Client Europe S.à r.l.',
            'trading_name' => 'Demo EU',
            'external_id' => 'COST-DEMO-EU',
            'tax_id' => 'LU12345678',
            'payment_terms_days' => 30,
            'credit_limit' => 90000.00,
            'bill_address_line1' => '12 Rue de la Gare',
            'bill_city' => 'Luxembourg',
            'bill_region' => 'LU',
            'bill_postal_code' => 'L-1611',
            'bill_country' => 'LU',
            'ship_same_as_bill' => true,
            'invoice_delivery_method' => 'edi',
            'customer_payment_method' => 'wire',
        ]);
    }
}
