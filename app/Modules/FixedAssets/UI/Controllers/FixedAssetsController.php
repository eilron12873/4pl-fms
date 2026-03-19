<?php

namespace App\Modules\FixedAssets\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use App\Core\Services\AuditService;
use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\AssetMaintenance;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class FixedAssetsController extends Controller
{
    public function __construct(
        protected FixedAssetService $fixedAssetService,
        protected AuditService $audit,
    ) {
    }

    public function index(): View
    {
        $activeCount = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->count();
        $totalCost = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->sum('acquisition_cost');
        $totalAccumDepn = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->sum('accumulated_depreciation');
        return view('fixed-assets::index', [
            'activeCount' => $activeCount,
            'totalCost' => $totalCost,
            'totalAccumDepn' => $totalAccumDepn,
            'bookValue' => $totalCost - $totalAccumDepn,
        ]);
    }

    public function assets(Request $request): View
    {
        $query = FixedAsset::orderBy('code');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('asset_type')) {
            $query->where('asset_type', $request->string('asset_type'));
        }
        $assets = $query->paginate(15);
        return view('fixed-assets::assets.index', compact('assets'));
    }

    public function assetCreate(): View
    {
        return view('fixed-assets::assets.create');
    }

    public function assetStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fixed_assets,code'],
            'name' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', 'string', 'in:vehicle,equipment,it,building,other'],
            'purchase_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],
            'useful_life_years' => ['required', 'integer', 'min:1', 'max:100'],
            'residual_value' => ['nullable', 'numeric', 'min:0', 'lt:acquisition_cost'],
            'gl_disposal_proceeds_code' => ['nullable', 'string', 'max:20'],
            'gl_disposal_gain_code' => ['nullable', 'string', 'max:20'],
            'gl_disposal_loss_code' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'custodian' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $this->fixedAssetService->register($data);
        return redirect()->route('fixed-assets.assets.index')->with('success', __('Asset registered.'));
    }

    public function assetShow(int $id): View
    {
        $asset = FixedAsset::with('maintenanceRecords')->findOrFail($id);
        return view('fixed-assets::assets.show', compact('asset'));
    }

    public function assetDispose(int $id): View|RedirectResponse
    {
        $asset = FixedAsset::findOrFail($id);
        if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
            return redirect()->route('fixed-assets.assets.show', $asset->id)->with('error', __('Only active assets can be disposed.'));
        }
        return view('fixed-assets::assets.dispose', compact('asset'));
    }

    public function assetDisposeStore(Request $request, int $id): RedirectResponse
    {
        $asset = FixedAsset::findOrFail($id);
        if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
            return redirect()->route('fixed-assets.assets.show', $asset->id)->with('error', __('Only active assets can be disposed.'));
        }

        $data = $request->validate([
            'proceeds' => ['required', 'numeric', 'min:0'],
            'disposed_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $wasActive = true;

        try {
            $this->fixedAssetService->dispose(
                asset: $asset,
                proceeds: (float) $data['proceeds'],
                disposedAt: $data['disposed_at'],
                reference: $data['reference'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors([
                'proceeds' => __($e->getMessage()),
            ]);
        }

        $asset->refresh();
        if ($wasActive && $asset->status === FixedAsset::STATUS_DISPOSED) {
            $this->audit->logFinancial(
                description: 'Fixed asset disposed and GL posted',
                subject: $asset,
                properties: [
                    'asset_code' => $asset->code,
                    'proceeds' => (float) $data['proceeds'],
                    'disposed_at' => $data['disposed_at'],
                    'reference' => $data['reference'] ?? null,
                ],
                event: 'fixed-assets.disposal.posted',
            );
        }

        return redirect()->route('fixed-assets.assets.show', $asset->id)->with('success', __('Asset disposed.'));
    }

    public function depreciation(): View
    {
        return view('fixed-assets::depreciation.index');
    }

    public function depreciationRun(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_end_date' => ['required', 'date'],
        ]);
        try {
            $results = $this->fixedAssetService->runDepreciation($data['period_end_date']);
            $posted = collect($results)->filter(fn ($r) => $r['journal_id'] !== null)->count();
            if ($posted > 0) {
                $periodEnd = Carbon::parse($data['period_end_date'])->endOfMonth()->toDateString();
                $this->audit->logFinancial(
                    description: 'Fixed assets depreciation run posted',
                    subject: null,
                    properties: [
                        'period_end_date' => $periodEnd,
                        'posted_assets' => $posted,
                    ],
                    event: 'fixed-assets.depreciation.posted',
                );
            }
            return redirect()->route('fixed-assets.depreciation.index')
                ->with('success', __('Depreciation run complete. :count asset(s) posted.', ['count' => $posted]));
        } catch (PeriodLockedException $e) {
            return redirect()->route('fixed-assets.depreciation.index')
                ->with('error', __($e->getMessage()));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withInput()->withErrors([
                'period_end_date' => __($e->getMessage()),
            ]);
        }
    }

    public function depreciationSchedule(Request $request): View
    {
        $query = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->orderBy('code');
        if ($request->filled('asset_type')) {
            $query->where('asset_type', $request->string('asset_type'));
        }
        $assets = $query->get();
        $schedule = [];
        foreach ($assets as $asset) {
            $monthsTotal = (int) $asset->useful_life_years * 12;
            $depreciableAmount = (float) $asset->depreciableAmount();
            $accumulated = (float) $asset->accumulated_depreciation;
            $remainingDepn = max(0.0, $depreciableAmount - $accumulated);

            $purchase = Carbon::parse($asset->purchase_date);
            $asOf = $asset->last_depreciation_at ? Carbon::parse($asset->last_depreciation_at) : $purchase->copy()->subMonth();
            $elapsedMonths = $purchase->diffInMonths($asOf->copy()->endOfMonth());
            $remainingMonths = max(0, $monthsTotal - $elapsedMonths);

            $monthlyDepnFull = ($monthsTotal > 0 && $depreciableAmount > 0)
                ? $depreciableAmount / $monthsTotal
                : 0.0;

            // Cap displayed monthly depreciation to what remains.
            $monthlyDepn = ($remainingDepn > 0 && $monthlyDepnFull > 0)
                ? min($monthlyDepnFull, $remainingDepn)
                : 0.0;

            // If we're fully depreciated, remaining months must be 0 (not computed from elapsedMonths).
            if ($remainingDepn <= 0.00001) {
                $monthlyDepn = 0.0;
                $remainingMonths = 0;
            }

            $annualDepn = $monthlyDepn * 12;
            $schedule[] = [
                'asset' => $asset,
                'monthly_depn' => round($monthlyDepn, 2),
                'annual_depn' => round($annualDepn, 2),
                'elapsed_months' => $elapsedMonths,
                'remaining_months' => $remainingMonths,
            ];
        }
        return view('fixed-assets::depreciation.schedule', compact('schedule', 'assets'));
    }

    public function depreciationHistory(Request $request): View
    {
        $query = PostingSource::with(['journal.lines'])
            ->where('source_system', 'fixed-assets')
            ->where('event_type', 'depreciation')
            ->orderByDesc('id');
        $sources = $query->paginate(20);
        $assetIds = $sources->getCollection()->map(fn ($s) => (int) $s->source_reference)->unique()->filter()->all();
        $assets = FixedAsset::whereIn('id', $assetIds)->get()->keyBy('id');
        return view('fixed-assets::depreciation.history', compact('sources', 'assets'));
    }

    public function reports(Request $request): View
    {
        $query = FixedAsset::withSum('maintenanceRecords', 'amount')
            ->orderBy('code');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('asset_type')) {
            $query->where('asset_type', $request->string('asset_type'));
        }
        $assets = $query->get();
        $costReport = $assets->map(fn ($a) => [
            'asset' => $a,
            'acquisition_cost' => (float) $a->acquisition_cost,
            'accumulated_depreciation' => (float) $a->accumulated_depreciation,
            'book_value' => $a->bookValue(),
            'total_maintenance' => (float) ($a->maintenance_records_sum_amount ?? 0),
            'total_cost' => $a->bookValue() + (float) ($a->maintenance_records_sum_amount ?? 0),
        ]);
        return view('fixed-assets::reports.index', compact('costReport', 'assets'));
    }

    public function maintenance(Request $request): View
    {
        $query = AssetMaintenance::with('fixedAsset')->orderByDesc('maintenance_date')->orderByDesc('id');
        if ($request->filled('fixed_asset_id')) {
            $query->where('fixed_asset_id', $request->integer('fixed_asset_id'));
        }
        $records = $query->paginate(20);
        $assets = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->orderBy('code')->get();
        return view('fixed-assets::maintenance.index', compact('records', 'assets'));
    }

    public function maintenanceCreate(): View
    {
        $assets = FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->orderBy('code')->get();
        return view('fixed-assets::maintenance.create', compact('assets'));
    }

    public function maintenanceStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fixed_asset_id' => ['required', 'exists:fixed_assets,id'],
            'maintenance_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $maintenance = $this->fixedAssetService->recordMaintenance(
            (int) $data['fixed_asset_id'],
            $data['maintenance_date'],
            (float) $data['amount'],
            $data['description'] ?? null,
            $data['reference'] ?? null,
        );
        $this->audit->log(
            description: 'Fixed asset maintenance recorded',
            event: 'fixed-assets.maintenance.recorded',
            subject: $maintenance,
            properties: [
                'fixed_asset_id' => $maintenance->fixed_asset_id,
                'maintenance_date' => $maintenance->maintenance_date?->toDateString(),
                'amount' => (float) $maintenance->amount,
                'reference' => $maintenance->reference ?? null,
            ],
        );
        return redirect()->route('fixed-assets.maintenance.index')->with('success', __('Maintenance recorded.'));
    }
}

