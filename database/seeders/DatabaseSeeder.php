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

