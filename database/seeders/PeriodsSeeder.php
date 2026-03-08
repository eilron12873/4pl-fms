<?php

namespace Database\Seeders;

use App\Modules\CoreAccounting\Infrastructure\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PeriodsSeeder extends Seeder
{
    /**
     * Seed one open period for the current month so posting is allowed.
     */
    public function run(): void
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $code = $start->format('Y-m');

        Period::firstOrCreate(
            ['code' => $code],
            [
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'open',
            ],
        );
    }
}
