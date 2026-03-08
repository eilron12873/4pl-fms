<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Income Statement Mappings
    |--------------------------------------------------------------------------
    | Each section has: key, label, account_prefixes (array of code prefixes to include).
    | Revenue sections are positive; expense sections are subtracted.
    */
    'income_statement' => [
        ['key' => 'revenue', 'label' => 'Revenue', 'account_prefixes' => ['41', '42', '43', '44']],
        ['key' => 'cost_of_revenue', 'label' => 'Cost of Revenue', 'account_prefixes' => ['53']],
        ['key' => 'operating_expenses', 'label' => 'Operating Expenses', 'account_prefixes' => ['51', '52', '54', '55']],
        ['key' => 'other_income', 'label' => 'Other Income', 'account_prefixes' => ['45', '46']],
        ['key' => 'other_expense', 'label' => 'Other Expense', 'account_prefixes' => ['56', '57']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Balance Sheet Mappings
    |--------------------------------------------------------------------------
    | Sections for assets, liabilities, equity. As-of-date balances.
    */
    'balance_sheet' => [
        ['key' => 'current_assets', 'label' => 'Current Assets', 'account_prefixes' => ['11', '12', '14']],
        ['key' => 'fixed_assets', 'label' => 'Fixed Assets', 'account_prefixes' => ['13']],
        ['key' => 'other_assets', 'label' => 'Other Assets', 'account_prefixes' => ['15', '16', '17', '18', '19']],
        ['key' => 'current_liabilities', 'label' => 'Current Liabilities', 'account_prefixes' => ['21', '22']],
        ['key' => 'long_term_liabilities', 'label' => 'Long-term Liabilities', 'account_prefixes' => ['23', '24', '25']],
        ['key' => 'equity', 'label' => 'Equity', 'account_prefixes' => ['31', '32', '33']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cash Flow (Indirect) - Operating Adjustments
    |--------------------------------------------------------------------------
    | Account prefixes that represent non-cash or working capital changes.
    | Positive = add back to net income, negative = subtract.
    */
    'cash_flow_operating_adjustments' => [
        ['key' => 'depreciation', 'label' => 'Depreciation & Amortization', 'account_prefixes' => ['58']],
        ['key' => 'change_receivables', 'label' => 'Change in Receivables', 'account_prefixes' => ['11']],
        ['key' => 'change_inventory', 'label' => 'Change in Inventory', 'account_prefixes' => ['12']],
        ['key' => 'change_payables', 'label' => 'Change in Payables', 'account_prefixes' => ['21', '22']],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cash Flow - Investing / Financing (optional v1)
    |--------------------------------------------------------------------------
    */
    'cash_flow_investing_prefixes' => ['13'],
    'cash_flow_financing_prefixes' => ['31', '32', '33'],
];
