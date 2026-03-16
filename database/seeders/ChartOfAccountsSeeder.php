<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed a logistics-oriented chart of accounts for LFS.
     * Account codes (6-digit XYYZZZ structure):
     * X = financial statement class (1 Assets, 2 Liabilities, 3 Equity, 4 Revenue,
     * 5 Cost of services, 6 Operating expenses, 7 Other income, 8 Other expenses)
     * YY = category, ZZZ = detailed account / subcategory.
     */
    public function run(): void
    {
        $accounts = [
            // --- BIR-ready core COA (bir_ready_chart_of_accounts.csv) ---
            // Assets
            ['code' => '100000', 'name' => 'Assets', 'type' => 'asset', 'level' => 1, 'is_posting' => false],
            ['code' => '110000', 'name' => 'Current Assets', 'type' => 'asset', 'level' => 2, 'is_posting' => false],
            ['code' => '111000', 'name' => 'Cash and Cash Equivalents', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '111100', 'name' => 'Cash on Hand', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '111200', 'name' => 'Petty Cash Fund', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '111300', 'name' => 'Change Fund', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '111400', 'name' => 'Cash in Transit', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '111500', 'name' => 'Undeposited Funds', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112000', 'name' => 'Cash in Bank', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '112100', 'name' => 'Cash in Bank - BDO', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112200', 'name' => 'Cash in Bank - BPI', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112300', 'name' => 'Cash in Bank - Metrobank', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112400', 'name' => 'Cash in Bank - Landbank', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112500', 'name' => 'Cash in Bank - Unionbank', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '112600', 'name' => 'Cash in Bank - Security Bank', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '113000', 'name' => 'Digital Wallet Accounts', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '113100', 'name' => 'GCash Wallet', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '113200', 'name' => 'Maya Wallet', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '113300', 'name' => 'PayPal Wallet', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '114000', 'name' => 'Short-Term Investments', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '114100', 'name' => 'Time Deposits', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '114200', 'name' => 'Treasury Bills', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '114300', 'name' => 'Money Market Placements', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '120000', 'name' => 'Receivables', 'type' => 'asset', 'level' => 2, 'is_posting' => false],
            ['code' => '121000', 'name' => 'Accounts Receivable', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '121100', 'name' => 'Trade Receivables', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121200', 'name' => 'Non-Trade Receivables', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121300', 'name' => 'Related Party Receivables', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121400', 'name' => 'Employee Receivables', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121500', 'name' => 'Advances to Employees', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121600', 'name' => 'Advances to Officers', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '121700', 'name' => 'Advances to Suppliers', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '122000', 'name' => 'Allowance for Doubtful Accounts', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '123000', 'name' => 'Notes Receivable', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '123100', 'name' => 'Short-term Notes Receivable', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '123200', 'name' => 'Long-term Notes Receivable', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '124000', 'name' => 'Interest Receivable', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '125000', 'name' => 'Dividends Receivable', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '126000', 'name' => 'VAT Receivable', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '126100', 'name' => 'Input VAT', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '126200', 'name' => 'Creditable Withholding Tax', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '126300', 'name' => 'VAT Refund Receivable', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '127000', 'name' => 'Other Receivables', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '127100', 'name' => 'Insurance Claims Receivable', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '127200', 'name' => 'Refund Receivable', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '130000', 'name' => 'Inventory', 'type' => 'asset', 'level' => 2, 'is_posting' => false],
            ['code' => '131000', 'name' => 'Merchandise Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '132000', 'name' => 'Raw Materials Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '133000', 'name' => 'Work in Process Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '134000', 'name' => 'Finished Goods Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '135000', 'name' => 'Packaging Materials Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '136000', 'name' => 'Spare Parts Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '137000', 'name' => 'Supplies Inventory', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '138000', 'name' => 'Inventory Adjustments', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '138100', 'name' => 'Inventory Shrinkage', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '138200', 'name' => 'Inventory Write-Down', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '138300', 'name' => 'Inventory Obsolescence', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '140000', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'level' => 2, 'is_posting' => false],
            ['code' => '141000', 'name' => 'Prepaid Rent', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '142000', 'name' => 'Prepaid Insurance', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '143000', 'name' => 'Prepaid Taxes', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '144000', 'name' => 'Prepaid Licenses', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '145000', 'name' => 'Prepaid Maintenance', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '146000', 'name' => 'Prepaid Software Subscriptions', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '147000', 'name' => 'Prepaid Advertising', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '148000', 'name' => 'Prepaid Utilities', 'type' => 'asset', 'level' => 3, 'is_posting' => true],
            ['code' => '150000', 'name' => 'Non-Current Assets', 'type' => 'asset', 'level' => 2, 'is_posting' => false],
            ['code' => '151000', 'name' => 'Investments', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '151100', 'name' => 'Investment in Subsidiary', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '151200', 'name' => 'Investment in Associate', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '151300', 'name' => 'Long-term Investments', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152000', 'name' => 'Property Plant and Equipment', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '152100', 'name' => 'Land', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152200', 'name' => 'Buildings', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152300', 'name' => 'Leasehold Improvements', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152400', 'name' => 'Machinery', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152500', 'name' => 'Equipment', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152600', 'name' => 'Office Furniture', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '152700', 'name' => 'Vehicles', 'type' => 'asset', 'level' => 4, 'is_posting' => false],
            ['code' => '152800', 'name' => 'IT Equipment', 'type' => 'asset', 'level' => 4, 'is_posting' => false],
            ['code' => '152900', 'name' => 'Warehouse Equipment', 'type' => 'asset', 'level' => 4, 'is_posting' => false],
            ['code' => '153000', 'name' => 'Accumulated Depreciation (Contra Asset)', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '153100', 'name' => 'Accumulated Depreciation - Buildings', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '153200', 'name' => 'Accumulated Depreciation - Machinery', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '153300', 'name' => 'Accumulated Depreciation - Equipment', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '153400', 'name' => 'Accumulated Depreciation - Vehicles', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '153500', 'name' => 'Accumulated Depreciation - Furniture', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '153600', 'name' => 'Accumulated Depreciation - IT Equipment', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154000', 'name' => 'Intangible Assets', 'type' => 'asset', 'level' => 3, 'is_posting' => false],
            ['code' => '154100', 'name' => 'Software', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154200', 'name' => 'Software Development', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154300', 'name' => 'Patents', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154400', 'name' => 'Copyrights', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154500', 'name' => 'Trademarks', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154600', 'name' => 'Franchise Rights', 'type' => 'asset', 'level' => 4, 'is_posting' => true],
            ['code' => '154700', 'name' => 'Goodwill', 'type' => 'asset', 'level' => 4, 'is_posting' => true],

            // Liabilities
            ['code' => '200000', 'name' => 'Liabilities', 'type' => 'liability', 'level' => 1, 'is_posting' => false],
            ['code' => '210000', 'name' => 'Current Liabilities', 'type' => 'liability', 'level' => 2, 'is_posting' => false],
            ['code' => '211000', 'name' => 'Accounts Payable', 'type' => 'liability', 'level' => 3, 'is_posting' => false],
            ['code' => '211100', 'name' => 'Trade Payables', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '211200', 'name' => 'Non-Trade Payables', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '211300', 'name' => 'Related Party Payables', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '212000', 'name' => 'Accrued Expenses', 'type' => 'liability', 'level' => 3, 'is_posting' => false],
            ['code' => '212100', 'name' => 'Accrued Salaries', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '212200', 'name' => 'Accrued Utilities', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '212300', 'name' => 'Accrued Rent', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '212400', 'name' => 'Accrued Interest', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '212500', 'name' => 'Accrued Professional Fees', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213000', 'name' => 'Taxes Payable', 'type' => 'liability', 'level' => 3, 'is_posting' => false],
            ['code' => '213100', 'name' => 'VAT Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213200', 'name' => 'Withholding Tax Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213300', 'name' => 'Expanded Withholding Tax', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213400', 'name' => 'Final Withholding Tax', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213500', 'name' => 'Income Tax Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213600', 'name' => 'Percentage Tax Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '213700', 'name' => 'Documentary Stamp Tax Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '214000', 'name' => 'Payroll Liabilities', 'type' => 'liability', 'level' => 3, 'is_posting' => false],
            ['code' => '214100', 'name' => 'SSS Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '214200', 'name' => 'PhilHealth Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '214300', 'name' => 'Pag-IBIG Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '214400', 'name' => 'Payroll Taxes Payable', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '215000', 'name' => 'Short-Term Loans', 'type' => 'liability', 'level' => 3, 'is_posting' => false],
            ['code' => '215100', 'name' => 'Bank Loans', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '215200', 'name' => 'Credit Lines', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '215300', 'name' => 'Revolving Credit', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '215400', 'name' => 'Notes Payable - Short Term', 'type' => 'liability', 'level' => 4, 'is_posting' => true],
            ['code' => '220000', 'name' => 'Long Term Liabilities', 'type' => 'liability', 'level' => 2, 'is_posting' => false],
            ['code' => '221000', 'name' => 'Long-term Bank Loans', 'type' => 'liability', 'level' => 3, 'is_posting' => true],
            ['code' => '222000', 'name' => 'Mortgage Payable', 'type' => 'liability', 'level' => 3, 'is_posting' => true],
            ['code' => '223000', 'name' => 'Lease Liability', 'type' => 'liability', 'level' => 3, 'is_posting' => true],
            ['code' => '224000', 'name' => 'Bonds Payable', 'type' => 'liability', 'level' => 3, 'is_posting' => true],
            ['code' => '225000', 'name' => 'Notes Payable - Long Term', 'type' => 'liability', 'level' => 3, 'is_posting' => true],
            ['code' => '226000', 'name' => 'Deferred Tax Liability', 'type' => 'liability', 'level' => 3, 'is_posting' => true],

            // Equity
            ['code' => '300000', 'name' => 'Equity', 'type' => 'equity', 'level' => 1, 'is_posting' => false],
            ['code' => '310000', 'name' => 'Share Capital', 'type' => 'equity', 'level' => 2, 'is_posting' => false],
            ['code' => '311000', 'name' => 'Common Stock', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '312000', 'name' => 'Preferred Stock', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '320000', 'name' => 'Additional Paid-in Capital', 'type' => 'equity', 'level' => 2, 'is_posting' => true],
            ['code' => '330000', 'name' => 'Retained Earnings', 'type' => 'equity', 'level' => 2, 'is_posting' => false],
            ['code' => '331000', 'name' => 'Prior Year Retained Earnings', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '332000', 'name' => 'Current Year Earnings', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '340000', 'name' => 'Dividends', 'type' => 'equity', 'level' => 2, 'is_posting' => false],
            ['code' => '341000', 'name' => 'Dividends Declared', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '342000', 'name' => 'Dividends Payable', 'type' => 'equity', 'level' => 3, 'is_posting' => true],
            ['code' => '350000', 'name' => 'Treasury Stock', 'type' => 'equity', 'level' => 2, 'is_posting' => true],

            // Revenue
            ['code' => '400000', 'name' => 'Revenue', 'type' => 'revenue', 'level' => 1, 'is_posting' => false],
            ['code' => '410000', 'name' => 'Service Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => false],
            ['code' => '411000', 'name' => 'Warehousing Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '412000', 'name' => 'Storage Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '413000', 'name' => 'Handling Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '414000', 'name' => 'Distribution Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '415000', 'name' => 'Logistics Management Fee', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '420000', 'name' => 'Transport Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => false],
            ['code' => '421000', 'name' => 'Trucking Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '422000', 'name' => 'Delivery Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '423000', 'name' => 'Freight Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '424000', 'name' => 'Container Handling Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '430000', 'name' => 'Other Service Revenue', 'type' => 'revenue', 'level' => 2, 'is_posting' => false],
            ['code' => '431000', 'name' => 'Consultation Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '432000', 'name' => 'Installation Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],
            ['code' => '433000', 'name' => 'Maintenance Revenue', 'type' => 'revenue', 'level' => 3, 'is_posting' => true],

            // Cost of Services
            ['code' => '500000', 'name' => 'Cost of Services', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '510000', 'name' => 'Direct Labor', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '511000', 'name' => 'Warehouse Labor', 'type' => 'expense', 'level' => 3, 'is_posting' => false],
            ['code' => '512000', 'name' => 'Drivers Wages', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '513000', 'name' => 'Helpers Wages', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '520000', 'name' => 'Handling Cost', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '521000', 'name' => 'Equipment Operations', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '522000', 'name' => 'Forklift Fuel', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '523000', 'name' => 'Warehouse Supplies', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '530000', 'name' => 'Transport Cost', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '531000', 'name' => 'Fuel Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => false],
            ['code' => '532000', 'name' => 'Truck Maintenance', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '533000', 'name' => 'Toll Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => false],
            ['code' => '534000', 'name' => 'Parking Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],

            // Operating Expenses
            ['code' => '600000', 'name' => 'Operating Expenses', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '610000', 'name' => 'Salaries Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '611000', 'name' => 'Office Salaries', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '612000', 'name' => 'Management Salaries', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '613000', 'name' => 'Employee Benefits', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '614000', 'name' => 'Overtime Pay', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '615000', 'name' => 'Bonuses', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '620000', 'name' => 'Office Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '621000', 'name' => 'Office Supplies', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '622000', 'name' => 'Printing and Stationery', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '623000', 'name' => 'Courier Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '624000', 'name' => 'Postage Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '625000', 'name' => 'Pantry Supplies', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '630000', 'name' => 'Facility Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '631000', 'name' => 'Rent Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '632000', 'name' => 'Utilities Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '633000', 'name' => 'Electricity Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '634000', 'name' => 'Water Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '635000', 'name' => 'Internet Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '636000', 'name' => 'Building Maintenance', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '640000', 'name' => 'Professional Fees', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '641000', 'name' => 'Accounting Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '642000', 'name' => 'Legal Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '643000', 'name' => 'Audit Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '644000', 'name' => 'Consulting Fees', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '650000', 'name' => 'Marketing Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '651000', 'name' => 'Advertising Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '652000', 'name' => 'Promotions', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '653000', 'name' => 'Sponsorship', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '654000', 'name' => 'Digital Marketing', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '660000', 'name' => 'IT Expenses', 'type' => 'expense', 'level' => 2, 'is_posting' => false],
            ['code' => '661000', 'name' => 'Software Subscription', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '662000', 'name' => 'Cloud Hosting', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '663000', 'name' => 'IT Maintenance', 'type' => 'expense', 'level' => 3, 'is_posting' => true],
            ['code' => '664000', 'name' => 'Cybersecurity Expense', 'type' => 'expense', 'level' => 3, 'is_posting' => true],

            // Other Income
            ['code' => '700000', 'name' => 'Other Income', 'type' => 'revenue', 'level' => 1, 'is_posting' => false],
            ['code' => '710000', 'name' => 'Interest Income', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '720000', 'name' => 'Gain on Sale of Assets', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '730000', 'name' => 'Foreign Exchange Gain', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '740000', 'name' => 'Rental Income', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],
            ['code' => '750000', 'name' => 'Miscellaneous Income', 'type' => 'revenue', 'level' => 2, 'is_posting' => true],

            // Other Expenses
            ['code' => '800000', 'name' => 'Other Expenses', 'type' => 'expense', 'level' => 1, 'is_posting' => false],
            ['code' => '810000', 'name' => 'Interest Expense', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '820000', 'name' => 'Bank Charges', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '830000', 'name' => 'Foreign Exchange Loss', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '840000', 'name' => 'Loss on Asset Disposal', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '850000', 'name' => 'Penalties and Fines', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '860000', 'name' => 'Donations', 'type' => 'expense', 'level' => 2, 'is_posting' => true],
            ['code' => '870000', 'name' => 'Extraordinary Loss', 'type' => 'expense', 'level' => 2, 'is_posting' => true],

            // --- Additional logistics-specific accounts from original LFS seeder (aligned to BIR groups) ---
            // Logistics fixed assets as PPE details under 152700 Vehicles / 152800 IT / 152900 Warehouse Equipment
            ['code' => '152710', 'name' => 'Trucks (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],
            ['code' => '152720', 'name' => 'Trailers (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],
            ['code' => '152730', 'name' => 'Forklifts (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],
            ['code' => '152740', 'name' => 'Containers (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],
            ['code' => '152910', 'name' => 'Warehouse Equipment (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],
            ['code' => '152810', 'name' => 'IT Equipment (Logistics)', 'type' => 'asset', 'level' => 5, 'is_posting' => true],

            // Additional logistics revenue breakouts under existing service revenue groups
            ['code' => '421100', 'name' => 'Domestic Transport Revenue (LFS Detail)', 'type' => 'revenue', 'level' => 4, 'is_posting' => true],
            ['code' => '422100', 'name' => 'International Freight Revenue (LFS Detail)', 'type' => 'revenue', 'level' => 4, 'is_posting' => true],
            ['code' => '423100', 'name' => 'Courier Revenue (LFS Detail)', 'type' => 'revenue', 'level' => 4, 'is_posting' => true],
            ['code' => '431100', 'name' => 'Project Cargo Revenue (LFS Detail)', 'type' => 'revenue', 'level' => 4, 'is_posting' => true],
            ['code' => '432100', 'name' => 'Special Handling Revenue (LFS Detail)', 'type' => 'revenue', 'level' => 4, 'is_posting' => true],

            // Logistics COS details aligned with BIR groups
            ['code' => '531100', 'name' => 'Fuel Expense (LFS Detail)', 'type' => 'expense', 'level' => 4, 'is_posting' => true],
            ['code' => '533100', 'name' => 'Toll Fees (LFS Detail)', 'type' => 'expense', 'level' => 4, 'is_posting' => true],
            ['code' => '531200', 'name' => 'Subcontracted Freight (LFS Detail)', 'type' => 'expense', 'level' => 4, 'is_posting' => true],
            ['code' => '511100', 'name' => 'Handling Labor (LFS Detail)', 'type' => 'expense', 'level' => 4, 'is_posting' => true],
        ];

        $parentIds = [];
        foreach ($accounts as $row) {
            // Parent logic for 6-digit XYYZZZ structure using trailing zeros:
            // 100000 -> level 1, no parent
            // 110000 -> level 2, parent 100000
            // 111000 -> level 3, parent 110000
            // 111100 -> level 4, parent 111000, etc.
            $code = $row['code'];
            $parentCode = null;

            if (strlen($code) === 6) {
                if (substr($code, 1) === '00000') {
                    // X00000: top-level class, no parent
                    $parentCode = null;
                } elseif (substr($code, 2) === '0000') {
                    // X Y 0000: category under X00000
                    $parentCode = $code[0] . '00000';
                } elseif (substr($code, 3) === '000') {
                    // X Y Y 000: subcategory under XYY000
                    $parentCode = substr($code, 0, 2) . '0000';
                } elseif (substr($code, 4) === '00') {
                    // X Y Y Z 00: detail group under XYYZ00
                    $parentCode = substr($code, 0, 3) . '000';
                } elseif (substr($code, 5) === '0') {
                    // X Y Y Z Z 0: deeper level, parent XYYZZ0
                    $parentCode = substr($code, 0, 4) . '00';
                } else {
                    // No trailing zeros: parent is code with last digit zeroed
                    $parentCode = substr($code, 0, 5) . '0';
                }
            }

            $parentId = $parentCode ? ($parentIds[$parentCode] ?? null) : null;

            $account = Account::firstOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'parent_id' => $parentId,
                    'level' => $row['level'],
                    'is_posting' => $row['is_posting'],
                ],
            );
            $parentIds[$row['code']] = $account->id;
        }
    }
}
