<?php

namespace Tests\Unit\FixedAssets;

use App\Modules\CoreAccounting\Infrastructure\Models\Journal;
use App\Modules\CoreAccounting\Infrastructure\Models\PostingSource;
use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_straight_line_calculate_depends_on_in_service_months(): void
    {
        $service = app(FixedAssetService::class);

        $asset = $service->register([
            'code' => 'FA-TEST-CALC-1',
            'name' => 'Calc asset 1',
            'asset_type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'acquisition_cost' => 1200.00,
            'useful_life_years' => 2, // 24 months
            'residual_value' => 200.00,
            'location' => null,
            'custodian' => null,
        ]);

        // Depreciable amount = 1000, monthly = 1000/24 = 41.666... => 41.67
        $amountFeb = $service->calculateDepreciationForPeriod($asset, '2026-02-01', '2026-02-28');
        $this->assertEquals(41.67, (float) $amountFeb, '', 0.01);

        // Purchase date in mid-March excludes March (current month start 2026-03-01 < 2026-03-15)
        $asset->update(['purchase_date' => '2026-03-15']);
        $amountAprWindow = $service->calculateDepreciationForPeriod($asset, '2026-02-01', '2026-04-30');
        // Only April counts => same amount as for 1 month.
        $this->assertEquals(41.67, (float) $amountAprWindow, '', 0.01);
    }

    public function test_run_depreciation_is_idempotent_for_same_period_end_date(): void
    {
        $service = app(FixedAssetService::class);

        $asset = $service->register([
            'code' => 'FA-TEST-IDEMP-1',
            'name' => 'Idempotency asset',
            'asset_type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'acquisition_cost' => 1200.00,
            'useful_life_years' => 2,
            'residual_value' => 200.00,
        ]);

        $periodEnd = '2026-01-31'; // End of month
        $fromDate = '2026-01-01';
        $idempotencyKey = 'fa-depn-' . $asset->id . '-' . $fromDate;

        $service->runDepreciation($periodEnd);
        $this->assertSame(1, PostingSource::where('idempotency_key', $idempotencyKey)->count());

        $journalCountAfterFirst = Journal::count();
        $service->runDepreciation($periodEnd);

        $this->assertSame(1, PostingSource::where('idempotency_key', $idempotencyKey)->count());
        $this->assertSame($journalCountAfterFirst, Journal::count());
    }

    public function test_disposal_journal_posts_correct_gain_account_when_proceeds_exceed_book_value(): void
    {
        $service = app(FixedAssetService::class);

        $asset = $service->register([
            'code' => 'FA-TEST-DISP-GAIN',
            'name' => 'Disposal gain asset',
            'asset_type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'acquisition_cost' => 12000.00,
            'useful_life_years' => 2,
            'residual_value' => 2000.00,
        ]);

        // Seed accumulated depreciation with 1 month posting (Jan)
        $service->runDepreciation('2026-01-31');

        $asset->refresh();
        $bookValue = (float) $asset->bookValue();

        $proceeds = $bookValue + 100.00;
        $disposedAt = '2026-02-28';

        $service->dispose(asset: $asset, proceeds: $proceeds, disposedAt: $disposedAt, reference: 'TEST-DISP-GAIN');

        $posting = PostingSource::where('source_system', 'fixed-assets')
            ->where('event_type', 'disposal')
            ->where('source_reference', (string) $asset->id)
            ->firstOrFail();

        $journal = $posting->journal;
        $lines = $journal->lines()->with('account')->get();

        $debitTotal = (float) $lines->sum('debit');
        $creditTotal = (float) $lines->sum('credit');
        $this->assertEquals(round($debitTotal, 2), round($creditTotal, 2));

        $gainLine = $lines->first(fn ($l) => $l->account->code === $asset->gl_disposal_gain_code);
        $this->assertNotNull($gainLine);
        $this->assertGreaterThan(0, (float) $gainLine->credit);

        $lossLine = $lines->first(fn ($l) => $l->account->code === $asset->gl_disposal_loss_code);
        $this->assertNull($lossLine);
    }

    public function test_disposal_journal_posts_correct_loss_account_when_proceeds_below_book_value(): void
    {
        $service = app(FixedAssetService::class);

        $asset = $service->register([
            'code' => 'FA-TEST-DISP-LOSS',
            'name' => 'Disposal loss asset',
            'asset_type' => 'equipment',
            'purchase_date' => '2026-01-01',
            'acquisition_cost' => 12000.00,
            'useful_life_years' => 2,
            'residual_value' => 2000.00,
        ]);

        $service->runDepreciation('2026-01-31');
        $asset->refresh();
        $bookValue = (float) $asset->bookValue();

        $proceeds = max(0.0, $bookValue - 100.00);
        $disposedAt = '2026-02-28';

        $service->dispose(asset: $asset, proceeds: $proceeds, disposedAt: $disposedAt, reference: 'TEST-DISP-LOSS');

        $posting = PostingSource::where('source_system', 'fixed-assets')
            ->where('event_type', 'disposal')
            ->where('source_reference', (string) $asset->id)
            ->firstOrFail();

        $journal = $posting->journal;
        $lines = $journal->lines()->with('account')->get();

        $lossLine = $lines->first(fn ($l) => $l->account->code === $asset->gl_disposal_loss_code);
        $this->assertNotNull($lossLine);
        $this->assertGreaterThan(0, (float) $lossLine->debit);

        $gainLine = $lines->first(fn ($l) => $l->account->code === $asset->gl_disposal_gain_code);
        $this->assertNull($gainLine);
    }
}

