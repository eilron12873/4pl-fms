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
            'integration.wms-billing', // WMS/integration layer can POST to wms-billing/feed
            'integration.financial-events', // API: POST /api/financial-events/{event_type}
            // Ensure core accounting management permission exists even if module
            // did not explicitly register it yet.
            'core-accounting.manage',
        ];

        $permissions = array_values(array_unique(array_merge($permissions, $corePermissions)));

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        $allPermissionNames = Permission::where('guard_name', $guard)->pluck('name')->all();

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => $guard,
        ]);
        $superAdmin->syncPermissions($allPermissionNames);

        // Admin: same permission set as Super Admin for v1. UserPolicy restricts who may assign
        // or edit Super Admin accounts (only Super Admin role holders).
        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => $guard,
        ]);
        $admin->syncPermissions($allPermissionNames);

        // Operational roles: subsets only. Adjust here when new permissions are added to modules.
        // Excludes user administration and integration API keys from non-admin roles.
        $noUserAdminOrIntegration = array_values(array_diff($allPermissionNames, [
            'lfs-administration.users.view',
            'lfs-administration.users.manage',
            'integration.wms-billing',
            'integration.financial-events',
        ]));

        $isViewish = static fn (string $n): bool => str_ends_with($n, '.view') || $n === 'reports.view';

        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => $guard]);
        $manager->syncPermissions($noUserAdminOrIntegration);

        $supervisorManage = [
            'accounts-receivable.manage',
            'accounts-payable.manage',
            'procurement.manage',
            'inventory-valuation.manage',
            'costing-engine.manage',
        ];
        $supervisorPerms = array_values(array_unique(array_merge(
            array_values(array_filter($allPermissionNames, $isViewish)),
            array_intersect($supervisorManage, $allPermissionNames),
        )));
        $supervisor = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guard]);
        $supervisor->syncPermissions($this->intersectPermissions($supervisorPerms, $allPermissionNames));

        $accountantWanted = [
            'core-accounting.view',
            'core-accounting.manage',
            'general-ledger.view',
            'accounts-receivable.view',
            'accounts-receivable.manage',
            'accounts-payable.view',
            'accounts-payable.manage',
            'financial-reporting.view',
            'reports.view',
            'treasury.view',
            'treasury.manage',
            'billing-engine.view',
            'billing-engine.manage',
            'costing-engine.view',
            'fixed-assets.view',
            'inventory-valuation.view',
        ];
        $accountant = Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => $guard]);
        $accountant->syncPermissions($this->intersectPermissions($accountantWanted, $allPermissionNames));

        $analystPerms = array_values(array_filter($allPermissionNames, $isViewish));
        $analyst = Role::firstOrCreate(['name' => 'Analyst', 'guard_name' => $guard]);
        $analyst->syncPermissions($this->intersectPermissions($analystPerms, $allPermissionNames));

        $staffWanted = [
            'general-ledger.view',
            'accounts-receivable.view',
            'accounts-payable.view',
            'financial-reporting.view',
            'reports.view',
            'procurement.view',
        ];
        $staff = Role::firstOrCreate(['name' => 'Staff', 'guard_name' => $guard]);
        $staff->syncPermissions($this->intersectPermissions($staffWanted, $allPermissionNames));
    }

    /**
     * @param  array<int, string>  $wanted
     * @param  array<int, string>  $existing
     * @return array<int, string>
     */
    protected function intersectPermissions(array $wanted, array $existing): array
    {
        return array_values(array_intersect($wanted, $existing));
    }
}
