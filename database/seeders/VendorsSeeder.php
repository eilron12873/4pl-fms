<?php

namespace Database\Seeders;

use App\Modules\AccountsPayable\Infrastructure\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorsSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['code' => 'V001', 'name' => 'Acme Freight Co', 'currency' => 'USD', 'payment_terms_days' => 30],
            ['code' => 'V002', 'name' => 'Global Storage Ltd', 'currency' => 'USD', 'payment_terms_days' => 45],
        ];

        foreach ($vendors as $row) {
            Vendor::firstOrCreate(
                ['code' => $row['code']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}
