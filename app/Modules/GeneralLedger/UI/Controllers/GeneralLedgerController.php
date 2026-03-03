<?php

namespace App\Modules\GeneralLedger\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\GeneralLedger\Application\ReportingService;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function __construct(
        protected ReportingService $reporting,
    ) {
    }

    public function index()
    {
        $accounts = Account::query()->orderBy('code')->get();

        return view('general-ledger::index', [
            'accounts' => $accounts,
        ]);
    }

    public function trialBalance()
    {
        $rows = $this->reporting->trialBalance();

        return view('general-ledger::trial-balance', [
            'rows' => $rows,
        ]);
    }

    public function generalLedger(Request $request)
    {
        $accountId = $request->integer('account_id') ?: null;
        $accounts = Account::query()->orderBy('code')->get();
        $result = $this->reporting->generalLedger($accountId);

        return view('general-ledger::general-ledger', [
            'accounts' => $accounts,
            'account' => $result['account'],
            'lines' => $result['lines'],
        ]);
    }
}

