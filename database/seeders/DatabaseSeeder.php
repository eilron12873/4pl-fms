<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ModulePermissionsSeeder::class);
        $this->call(ChartOfAccountsSeeder::class);
        $this->call(PeriodsSeeder::class);
        $this->call(ServiceTypesSeeder::class);
        $this->call(VendorsSeeder::class);
        $this->call(TreasurySeeder::class);
        $this->call(InventorySeeder::class);
        $this->call(FixedAssetsSeeder::class);
        $this->call(CostingEngineDemoSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('password'),
            ],
        );

        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if ($superAdminRole && ! $user->hasRole($superAdminRole)) {
            $user->assignRole($superAdminRole);
        }
    }
}

