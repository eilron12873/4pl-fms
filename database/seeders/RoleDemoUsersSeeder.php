<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleDemoUsersSeeder extends Seeder
{
    /**
     * Ensure every web role has at least one user (idempotent via email).
     * Normally run after {@see ModulePermissionsSeeder}. If no web roles exist yet, permissions are seeded first.
     */
    public function run(): void
    {
        if (! Role::query()->where('guard_name', 'web')->exists()) {
            $this->call(ModulePermissionsSeeder::class);
        }

        $demoPassword = Hash::make('password');

        $rows = [
            ['email' => 'demo-superadmin@4pl-fms.local', 'name' => 'Demo Super Admin', 'role' => 'Super Admin'],
            ['email' => 'demo-admin@4pl-fms.local', 'name' => 'Demo Admin', 'role' => 'Admin'],
            ['email' => 'demo-manager@4pl-fms.local', 'name' => 'Demo Manager', 'role' => 'Manager'],
            ['email' => 'demo-supervisor@4pl-fms.local', 'name' => 'Demo Supervisor', 'role' => 'Supervisor'],
            ['email' => 'demo-accountant@4pl-fms.local', 'name' => 'Demo Accountant', 'role' => 'Accountant'],
            ['email' => 'demo-analyst@4pl-fms.local', 'name' => 'Demo Analyst', 'role' => 'Analyst'],
            ['email' => 'demo-staff@4pl-fms.local', 'name' => 'Demo Staff', 'role' => 'Staff'],
        ];

        foreach ($rows as $row) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $row['role'])
                ->first();

            if ($role === null) {
                $this->command?->warn("Skipping {$row['email']}: role \"{$row['role']}\" not found.");

                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $demoPassword,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'department' => 'Demo',
                    'position' => $row['role'],
                ],
            );

            $user->syncRoles([$role->name]);
        }
    }
}
