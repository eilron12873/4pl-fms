<?php

namespace App\Modules\FixedAssets\UI\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CoreAccounting\Domain\Exceptions\PeriodLockedException;
use App\Modules\FixedAssets\Application\FixedAssetService;
use App\Modules\FixedAssets\Infrastructure\Models\FixedAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FixedAssetsApiController extends Controller
{
    public function __construct(
        protected FixedAssetService $fixedAssetService,
    ) {
    }

    public function depreciationRun(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period_end_date' => ['required', 'date'],
        ]);

        try {
            $results = $this->fixedAssetService->runDepreciation($data['period_end_date']);
            $posted = collect($results)->filter(fn ($r) => $r['journal_id'] !== null)->count();

            return response()->json([
                'success' => true,
                'posted_assets' => $posted,
                'results' => $results,
            ]);
        } catch (PeriodLockedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function maintenanceStore(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'maintenance_id' => $maintenance->id,
        ], 201);
    }

    public function disposeAsset(Request $request, int $id): JsonResponse
    {
        $asset = FixedAsset::findOrFail($id);
        if ($asset->status !== FixedAsset::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Only active assets can be disposed.',
            ], 422);
        }

        $data = $request->validate([
            'proceeds' => ['required', 'numeric', 'min:0'],
            'disposed_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->fixedAssetService->dispose(
                asset: $asset,
                proceeds: (float) $data['proceeds'],
                disposedAt: $data['disposed_at'],
                reference: $data['reference'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $asset->refresh();

        return response()->json([
            'success' => true,
            'asset_id' => $asset->id,
            'status' => $asset->status,
        ]);
    }
}

