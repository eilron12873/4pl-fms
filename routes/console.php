<?php

use App\Events\JournalPosted;
use App\Modules\AccountsPayable\Application\ApReportingService;
use App\Modules\AccountsPayable\Application\BillService;
use App\Modules\AccountsPayable\Infrastructure\Models\ApBill;
use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use App\Modules\CoreAccounting\Application\FinancialEventDispatcher;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ap:e2e', function () {
    $this->info('AP end-to-end: seed vendor, post event, issue bill, record payment, statement & aging');

    $vendor = Vendor::where('is_active', true)->first();
    if (! $vendor) {
        $this->warn('No vendor found. Run: php artisan db:seed --class=VendorsSeeder');
        return 1;
    }
    $this->line('Using vendor: ' . $vendor->code . ' - ' . $vendor->name . ' (id=' . $vendor->id . ')');

    $dispatcher = app(FinancialEventDispatcher::class);
    $payload = [
        'amount' => 1500.00,
        'vendor_id' => $vendor->id,
        'description' => 'E2E test freight charge',
        'journal_date' => now()->toDateString(),
    ];
    $context = [
        'idempotency_key' => 'ap-e2e-' . now()->format('YmdHis'),
        'source_system' => 'ap-e2e',
        'source_reference' => 'e2e-test-1',
    ];

    $result = $dispatcher->dispatch('vendor-invoice-approved', $payload, $context);
    if (($result['status'] ?? '') !== 'posted' || empty($result['journal_id'])) {
        $this->error('Event dispatch failed: ' . json_encode($result));
        return 1;
    }
    $this->info('Posted journal_id: ' . $result['journal_id']);

    $journal = Journal::with('lines')->find($result['journal_id']);
    if ($journal) {
        JournalPosted::dispatch($journal, [
            'event_type' => 'vendor-invoice-approved',
            'payload' => $payload,
            'source_system' => $context['source_system'],
            'source_reference' => $context['source_reference'],
        ]);
        $this->info('Dispatched JournalPosted -> AP bill line created');
    }

    $bill = ApBill::where('vendor_id', $vendor->id)->where('status', 'draft')->orderByDesc('id')->first();
    if (! $bill) {
        $this->error('No draft bill found for vendor.');
        return 1;
    }
    $this->line('Draft bill: ' . $bill->bill_number . ' total=' . $bill->total);

    $billService = app(BillService::class);
    $billService->issueBill($bill);
    $this->info('Bill issued.');

    $bill->refresh();
    $billService->recordPayment([
        'vendor_id' => $vendor->id,
        'payment_date' => now()->toDateString(),
        'amount' => 800.00,
        'currency' => $vendor->currency,
        'reference' => 'E2E-PMT-001',
        'allocations' => [
            ['bill_id' => $bill->id, 'amount' => 800.00],
        ],
    ]);
    $this->info('Payment 800.00 recorded and allocated to bill.');

    $reporting = app(ApReportingService::class);
    $statement = $reporting->statementOfAccount($vendor->id);
    $this->newLine();
    $this->info('--- Statement of Account (' . $vendor->code . ') ---');
    $this->line('Balance: ' . $statement['balance'] . ' ' . $statement['vendor']->currency);
    $this->line('Bills: ' . $statement['bills']->count() . ', Payments: ' . $statement['payments']->count());

    $aging = $reporting->agingReport(now()->toDateString());
    $this->info('--- AP Aging ---');
    foreach ($aging->take(5) as $row) {
        $this->line($row['vendor_code'] . ' - ' . $row['vendor_name'] . ': total=' . $row['total'] . ' (current=' . $row['current'] . ', 30=' . $row['days_30'] . ', 60=' . $row['days_60'] . ', 90=' . $row['days_90'] . ', 90+=' . $row['over_90'] . ')');
    }
    if ($aging->isEmpty()) {
        $this->line('(no outstanding balances)');
    }

    $this->newLine();
    $this->info('AP e2e completed. Check UI: bills, statement, aging, payments.');
    return 0;
})->purpose('Run AP end-to-end: vendor, vendor-invoice-approved event, issue bill, payment, statement & aging');
