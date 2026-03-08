<?php

namespace Database\Seeders;

use App\Modules\BillingEngine\Infrastructure\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'freight', 'name' => 'Freight', 'description' => 'Freight / shipment delivery'],
            ['code' => 'storage', 'name' => 'Storage', 'description' => 'Warehouse storage (per pallet/day)'],
            ['code' => 'courier', 'name' => 'Courier', 'description' => 'Courier / last mile'],
            ['code' => 'project_milestone', 'name' => 'Project Milestone', 'description' => 'Project-based milestone billing'],
            ['code' => 'handling', 'name' => 'Handling', 'description' => 'Handling / labour'],
        ];

        foreach ($types as $row) {
            ServiceType::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'description' => $row['description'] ?? null],
            );
        }
    }
}
