<?php

return [
    'menu' => [
        [
            'label' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'order' => 10,
            'children' => [
                [
                    'label' => 'Executive Dashboard',
                    'route' => 'dashboard.index',
                    'icon' => 'fas fa-chart-line',
                    'nav_key' => 'dashboard_executive',
                ],
                [
                    'label' => 'Operations Financial Snapshot',
                    'route' => 'dashboard.index',
                    'icon' => 'fas fa-project-diagram',
                    'nav_key' => 'dashboard_operations',
                ],
            ],
        ],
        [
            'label' => 'Core Accounting',
            'icon' => 'fas fa-balance-scale',
            'order' => 20,
            'permission' => 'core-accounting.view',
            'children' => [
                [
                    'label' => 'Chart of Accounts',
                    'route' => 'core-accounting.index',
                    'icon' => 'fas fa-sitemap',
                    'nav_key' => 'core_chart_of_accounts',
                ],
                [
                    'label' => 'Journal Management',
                    'route' => 'core-accounting.index',
                    'icon' => 'fas fa-book-open',
                    'nav_key' => 'core_journal_management',
                ],
                [
                    'label' => 'Period Management',
                    'route' => 'core-accounting.index',
                    'icon' => 'fas fa-calendar-alt',
                    'nav_key' => 'core_period_management',
                ],
            ],
        ],
        [
            'label' => 'Accounts Receivable',
            'icon' => 'fas fa-file-invoice-dollar',
            'order' => 30,
            'permission' => 'accounts-receivable.view',
            'children' => [
                [
                    'label' => 'Clients',
                    'route' => 'accounts-receivable.index',
                    'icon' => 'fas fa-user-friends',
                    'nav_key' => 'ar_clients',
                ],
                [
                    'label' => 'Contracts & Rate Cards',
                    'route' => 'accounts-receivable.index',
                    'icon' => 'fas fa-file-contract',
                    'nav_key' => 'ar_contracts',
                ],
                [
                    'label' => 'Billing Engine',
                    'route' => 'billing-engine.index',
                    'icon' => 'fas fa-cash-register',
                    'permission' => 'billing-engine.view',
                    'nav_key' => 'ar_billing_engine',
                ],
                [
                    'label' => 'Invoices',
                    'route' => 'accounts-receivable.index',
                    'icon' => 'fas fa-file-invoice',
                    'nav_key' => 'ar_invoices',
                ],
                [
                    'label' => 'Payments & Collections',
                    'route' => 'accounts-receivable.index',
                    'icon' => 'fas fa-hand-holding-usd',
                    'nav_key' => 'ar_payments',
                ],
            ],
        ],
        [
            'label' => 'Accounts Payable',
            'icon' => 'fas fa-file-invoice',
            'order' => 40,
            'permission' => 'accounts-payable.view',
            'children' => [
                [
                    'label' => 'Vendors',
                    'route' => 'accounts-payable.index',
                    'icon' => 'fas fa-truck-loading',
                    'nav_key' => 'ap_vendors',
                ],
                [
                    'label' => 'Vendor Contracts',
                    'route' => 'accounts-payable.index',
                    'icon' => 'fas fa-file-contract',
                    'nav_key' => 'ap_contracts',
                ],
                [
                    'label' => 'Vendor Bills',
                    'route' => 'accounts-payable.index',
                    'icon' => 'fas fa-file-invoice',
                    'nav_key' => 'ap_bills',
                ],
                [
                    'label' => 'Payments',
                    'route' => 'accounts-payable.index',
                    'icon' => 'fas fa-money-check-alt',
                    'nav_key' => 'ap_payments',
                ],
            ],
        ],
        [
            'label' => 'Costing & Profitability',
            'icon' => 'fas fa-balance-scale-right',
            'order' => 50,
            'permission' => 'costing-engine.view',
            'children' => [
                [
                    'label' => 'Shipment Profitability',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-boxes',
                    'nav_key' => 'costing_shipment',
                ],
                [
                    'label' => 'Client Profitability',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-user-tie',
                    'nav_key' => 'costing_client',
                ],
                [
                    'label' => 'Route Profitability',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-route',
                    'nav_key' => 'costing_route',
                ],
                [
                    'label' => 'Warehouse Profitability',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-warehouse',
                    'nav_key' => 'costing_warehouse',
                ],
                [
                    'label' => 'Project Profitability',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-project-diagram',
                    'nav_key' => 'costing_project',
                ],
                [
                    'label' => 'Allocation Engine',
                    'route' => 'costing-engine.index',
                    'icon' => 'fas fa-sliders-h',
                    'nav_key' => 'costing_allocation',
                ],
            ],
        ],
        [
            'label' => 'Inventory Control',
            'icon' => 'fas fa-box-open',
            'order' => 60,
            'permission' => 'inventory-valuation.view',
            'children' => [
                [
                    'label' => 'Inventory Valuation',
                    'route' => 'inventory-valuation.index',
                    'icon' => 'fas fa-layer-group',
                    'nav_key' => 'inventory_valuation',
                ],
                [
                    'label' => 'Stock Movements',
                    'route' => 'inventory-valuation.index',
                    'icon' => 'fas fa-exchange-alt',
                    'nav_key' => 'inventory_movements',
                ],
                [
                    'label' => 'Write-Off & Adjustments',
                    'route' => 'inventory-valuation.index',
                    'icon' => 'fas fa-adjust',
                    'nav_key' => 'inventory_adjustments',
                ],
            ],
        ],
        [
            'label' => 'Fixed Assets',
            'icon' => 'fas fa-truck-moving',
            'order' => 70,
            'permission' => 'fixed-assets.view',
            'children' => [
                [
                    'label' => 'Asset Registry',
                    'route' => 'fixed-assets.index',
                    'icon' => 'fas fa-clipboard-list',
                    'nav_key' => 'fa_registry',
                ],
                [
                    'label' => 'Depreciation',
                    'route' => 'fixed-assets.index',
                    'icon' => 'fas fa-hourglass-half',
                    'nav_key' => 'fa_depreciation',
                ],
                [
                    'label' => 'Maintenance Cost Tracking',
                    'route' => 'fixed-assets.index',
                    'icon' => 'fas fa-tools',
                    'nav_key' => 'fa_maintenance',
                ],
            ],
        ],
        [
            'label' => 'Treasury & Cash',
            'icon' => 'fas fa-piggy-bank',
            'order' => 80,
            'permission' => 'treasury.view',
            'children' => [
                [
                    'label' => 'Bank Accounts',
                    'route' => 'treasury.index',
                    'icon' => 'fas fa-university',
                    'nav_key' => 'treasury_accounts',
                ],
                [
                    'label' => 'Bank Reconciliation',
                    'route' => 'treasury.index',
                    'icon' => 'fas fa-balance-scale',
                    'nav_key' => 'treasury_reconciliation',
                ],
                [
                    'label' => 'Cash Management',
                    'route' => 'treasury.index',
                    'icon' => 'fas fa-wallet',
                    'nav_key' => 'treasury_cash',
                ],
            ],
        ],
        [
            'label' => 'Financial Reports',
            'icon' => 'fas fa-chart-pie',
            'order' => 90,
            'children' => [
                [
                    'label' => 'General Reports',
                    'route' => 'general-ledger.index',
                    'icon' => 'fas fa-book',
                    'permission' => 'general-ledger.view',
                    'nav_key' => 'reports_general',
                ],
                [
                    'label' => 'Trial Balance',
                    'route' => 'general-ledger.trial-balance',
                    'icon' => 'fas fa-balance-scale',
                    'permission' => 'general-ledger.view',
                    'nav_key' => 'reports_trial_balance',
                ],
                [
                    'label' => 'General Ledger',
                    'route' => 'general-ledger.ledger',
                    'icon' => 'fas fa-book-open',
                    'permission' => 'general-ledger.view',
                    'nav_key' => 'reports_general_ledger',
                ],
                [
                    'label' => 'Financial Statements',
                    'route' => 'financial-reporting.index',
                    'icon' => 'fas fa-file-alt',
                    'permission' => 'financial-reporting.view',
                    'nav_key' => 'reports_financial_statements',
                ],
                [
                    'label' => 'Management Reports',
                    'route' => 'financial-reporting.index',
                    'icon' => 'fas fa-chart-line',
                    'permission' => 'financial-reporting.view',
                    'nav_key' => 'reports_management',
                ],
                [
                    'label' => 'Tax Summary',
                    'route' => 'financial-reporting.index',
                    'icon' => 'fas fa-receipt',
                    'permission' => 'financial-reporting.view',
                    'nav_key' => 'reports_tax_summary',
                ],
            ],
        ],
        [
            'label' => 'Integration Center',
            'icon' => 'fas fa-plug',
            'order' => 100,
            'permission' => 'lfs-administration.view',
            'children' => [
                [
                    'label' => 'Financial Events Monitor',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-stream',
                    'nav_key' => 'integration_events',
                ],
                [
                    'label' => 'Sync Logs',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-clipboard-list',
                    'nav_key' => 'integration_sync_logs',
                ],
            ],
        ],
        [
            'label' => 'Approval Workflows',
            'icon' => 'fas fa-check-circle',
            'order' => 110,
            'permission' => 'lfs-administration.view',
            'children' => [
                [
                    'label' => 'Journal Approval',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-book-reader',
                    'nav_key' => 'workflow_journal',
                ],
                [
                    'label' => 'Invoice Approval',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'nav_key' => 'workflow_invoice',
                ],
                [
                    'label' => 'Vendor Bill Approval',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-file-invoice',
                    'nav_key' => 'workflow_vendor_bill',
                ],
                [
                    'label' => 'Allocation Approval',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-sliders-h',
                    'nav_key' => 'workflow_allocation',
                ],
                [
                    'label' => 'Credit Note Approval',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-file-invoice',
                    'nav_key' => 'workflow_credit_note',
                ],
            ],
        ],
        [
            'label' => 'Audit & Governance',
            'icon' => 'fas fa-user-shield',
            'order' => 120,
            'permission' => 'lfs-administration.view',
            'children' => [
                [
                    'label' => 'Audit Logs',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-clipboard-check',
                    'nav_key' => 'governance_audit_logs',
                ],
                [
                    'label' => 'Role & Permission Management',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-users-cog',
                    'nav_key' => 'governance_roles',
                ],
            ],
        ],
        [
            'label' => 'System Settings',
            'icon' => 'fas fa-cogs',
            'order' => 130,
            'permission' => 'lfs-administration.view',
            'children' => [
                [
                    'label' => 'Company Settings',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-building',
                    'nav_key' => 'settings_company',
                ],
                [
                    'label' => 'Financial Controls',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-lock',
                    'nav_key' => 'settings_financial_controls',
                ],
                [
                    'label' => 'Tax Configuration',
                    'route' => 'lfs-administration.index',
                    'icon' => 'fas fa-percentage',
                    'nav_key' => 'settings_tax',
                ],
            ],
        ],
    ],
];

