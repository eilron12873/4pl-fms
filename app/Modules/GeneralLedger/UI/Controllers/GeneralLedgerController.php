<?php

namespace App\Modules\GeneralLedger\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use App\Modules\GeneralLedger\Application\ReportingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function __construct(
        protected ReportingService $reporting,
    ) {
    }

    public function index(): View
    {
        $accounts = Account::query()->orderBy('code')->get();

        return view('general-ledger::index', [
            'accounts' => $accounts,
        ]);
    }

    public function trialBalance(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: null;
        $toDate = $request->string('to_date')->toString() ?: null;
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }
        if ($fromDate && ! $toDate) {
            $toDate = now()->toDateString();
        }

        $rows = $this->reporting->trialBalance($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('general-ledger::trial-balance', [
            'rows' => $rows,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'periods' => $periods,
        ]);
    }

    public function generalLedger(Request $request): View
    {
        $accountId = $request->integer('account_id') ?: null;
        $fromDate = $request->string('from_date')->toString() ?: null;
        $toDate = $request->string('to_date')->toString() ?: null;
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $accounts = Account::query()->orderBy('code')->get();
        $result = $this->reporting->generalLedger($accountId, $fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('general-ledger::general-ledger', [
            'accounts' => $accounts,
            'account' => $result['account'],
            'lines' => $result['lines'],
            'paginator' => $result['paginator'],
            'opening_balance' => $result['opening_balance'] ?? 0,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'periods' => $periods,
        ]);
    }

    public function incomeStatement(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->reporting->incomeStatement($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('general-ledger::income-statement', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }

    public function balanceSheet(Request $request): View
    {
        $asOfDate = $request->string('as_of_date')->toString() ?: now()->toDateString();

        $data = $this->reporting->balanceSheet($asOfDate);

        return view('general-ledger::balance-sheet', [
            'data' => $data,
        ]);
    }

    public function cashFlow(Request $request): View
    {
        $fromDate = $request->string('from_date')->toString() ?: now()->startOfMonth()->toDateString();
        $toDate = $request->string('to_date')->toString() ?: now()->toDateString();
        $periodCode = $request->string('period')->toString() ?: null;

        if ($periodCode) {
            $period = Period::where('code', $periodCode)->first();
            if ($period) {
                $fromDate = $period->start_date->toDateString();
                $toDate = $period->end_date->toDateString();
            }
        }

        $data = $this->reporting->cashFlowIndirect($fromDate, $toDate);
        $periods = Period::orderByDesc('start_date')->limit(24)->get();

        return view('general-ledger::cash-flow', [
            'data' => $data,
            'periods' => $periods,
        ]);
    }
}

