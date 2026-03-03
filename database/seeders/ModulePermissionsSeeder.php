<?php

namespace Database\Seeders;

use App\Core\ModuleManager;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModulePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = 'web';

        /** @var ModuleManager $moduleManager */
        $moduleManager = app(ModuleManager::class);

        $permissions = [];

        foreach ($moduleManager->getEnabledModules() as $module) {
            $permissions = array_merge($permissions, $module->getPermissions());
        }

        // Core (non-module) permissions used by navigation or core features
        $corePermissions = [
            'reports.view',
        ];

        $permissions = array_values(array_unique(array_merge($permissions, $corePermissions)));

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => $guard,
        ]);

        $role->givePermissionTo(Permission::where('guard_name', $guard)->pluck('name')->all());
    }
}

