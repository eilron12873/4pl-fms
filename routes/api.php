<?php

use App\Http\Controllers\Api\WmsBillingFeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
| Health check for deployment and monitoring (no auth).
| GET /api/health returns JSON: status, database, queue (optional).
*/
Route::get('/health', function () {
    $checks = ['status' => 'ok', 'database' => 'unknown', 'timestamp' => now()->toIso8601String()];

    try {
        DB::connection()->getPdo();
        DB::connection()->getDatabaseName();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'fail';
        $checks['status'] = 'degraded';
        $checks['database_error'] = $e->getMessage();
    }

    $code = ($checks['status'] === 'ok') ? 200 : 503;

    return response()->json($checks, $code);
})->name('api.health');

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

/*
| WMS Billing Feed: minimal data from WMS to create storage/handling revenue.
| Custody data stays in WMS; FMS receives only what is needed to bill.
| POST payload: client_id, event_type (storage-accrual|handling-accrual), event_date, pallet_days? (storage), quantity? (handling), reference?
*/
Route::middleware(['auth:sanctum', 'permission:integration.wms-billing'])->prefix('wms-billing')->group(function () {
    Route::post('/feed', [WmsBillingFeedController::class, 'feed'])->name('api.wms-billing.feed');
});

