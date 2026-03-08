<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\Account;
use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoreAccountingController extends Controller
{
    public function __construct(
        protected AuditService $audit
    ) {}
    public function index(): View
    {
        return view('core-accounting::index');
    }

    public function accounts(): View
    {
        $accounts = Account::with('parent')->orderBy('code')->paginate(50);
        return view('core-accounting::accounts.index', compact('accounts'));
    }

    public function accountShow(int $id): View
    {
        $account = Account::with('parent', 'children')->findOrFail($id);
        return view('core-accounting::accounts.show', compact('account'));
    }

    public function journals(): View
    {
        $journals = Journal::withCount('lines')->orderByDesc('journal_date')->orderByDesc('id')->paginate(20);
        return view('core-accounting::journals.index', compact('journals'));
    }

    public function journalShow(int $id): View
    {
        $journal = Journal::with(['lines.account', 'postingSource', 'reversalLinkAsOriginal.reversal', 'reversalLinkAsReversal.original'])
            ->findOrFail($id);
        return view('core-accounting::journals.show', compact('journal'));
    }

    public function postingSources(): View
    {
        $sources = PostingSource::with('journal')->orderByDesc('id')->paginate(30);
        return view('core-accounting::posting-sources.index', compact('sources'));
    }

    public function periods(): View
    {
        $periods = Period::orderByDesc('start_date')->paginate(24);
        return view('core-accounting::periods.index', compact('periods'));
    }

    public function closePeriod(Request $request, int $id): RedirectResponse
    {
        $this->authorize('core-accounting.manage');
        $period = Period::findOrFail($id);
        if ($period->isClosed()) {
            return redirect()->route('core-accounting.periods.index')->with('error', __('Period is already closed.'));
        }
        $period->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        $this->audit->logFinancial(
            "Period closed: {$period->code} ({$period->start_date?->toDateString()} to {$period->end_date?->toDateString()})",
            $period,
            ['period_code' => $period->code],
            'period.closed',
        );
        return redirect()->route('core-accounting.periods.index')->with('success', __('Period closed.'));
    }
}

