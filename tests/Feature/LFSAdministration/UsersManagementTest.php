<?php

namespace Tests\Feature\LFSAdministration;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UsersManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ModulePermissionsSeeder::class);
    }

    public function test_users_index_forbidden_without_users_view_permission(): void
    {
        Permission::findOrCreate('lfs-administration.view', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('lfs-administration.view');

        $this->actingAs($user)
            ->get(route('lfs-administration.settings.users'))
            ->assertStatus(403);
    }

    public function test_users_index_ok_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get(route('lfs-administration.settings.users'))
            ->assertOk();
    }

    public function test_super_admin_can_create_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $actor = User::factory()->create();
        $actor->assignRole('Super Admin');

        $this->actingAs($actor)
            ->post(route('lfs-administration.settings.users.store'), [
                'name' => 'New Operator',
                'email' => 'operator@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'role' => 'Admin',
                'department' => 'Ops',
                'position' => 'Clerk',
                'is_active' => '1',
            ])
            ->assertRedirect(route('lfs-administration.settings.users'));

        $this->assertDatabaseHas('users', [
            'email' => 'operator@example.com',
            'department' => 'Ops',
            'is_active' => true,
        ]);

        $this->assertTrue(User::where('email', 'operator@example.com')->first()?->hasRole('Admin'));
    }

    public function test_admin_cannot_assign_super_admin_on_create(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $actor = User::factory()->create();
        $actor->assignRole('Admin');

        $this->actingAs($actor)
            ->post(route('lfs-administration.settings.users.store'), [
                'name' => 'Bad',
                'email' => 'bad@example.com',
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
                'role' => UserPolicy::ROLE_SUPER_ADMIN,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_cannot_remove_last_super_admin_via_update(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $onlySuper = User::factory()->create(['email' => 'onlysuper@example.com']);
        $onlySuper->assignRole('Super Admin');

        $this->actingAs($onlySuper)
            ->put(route('lfs-administration.settings.users.update', $onlySuper), [
                'name' => $onlySuper->name,
                'email' => $onlySuper->email,
                'role' => 'Admin',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cannot_delete_self(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->delete(route('lfs-administration.settings.users.destroy', $user))
            ->assertRedirect(route('lfs-administration.settings.users'))
            ->assertSessionHas('error');
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);
        $user->assignRole('Super Admin');

        $this->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_non_super_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $target = User::factory()->create(['email' => 'target@example.com']);
        $target->assignRole('Admin');

        $this->actingAs($admin)
            ->put(route('lfs-administration.settings.users.update', $target), [
                'name' => 'Updated Name',
                'email' => 'target@example.com',
                'role' => 'Admin',
                'department' => 'HQ',
                'is_active' => '1',
            ])
            ->assertRedirect(route('lfs-administration.settings.users'));

        $this->assertSame('Updated Name', $target->fresh()->name);
        $this->assertSame('HQ', $target->fresh()->department);
    }

    public function test_admin_cannot_update_super_admin_user(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $super = User::factory()->create(['email' => 'super2@example.com']);
        $super->assignRole('Super Admin');

        $this->actingAs($admin)
            ->put(route('lfs-administration.settings.users.update', $super), [
                'name' => 'Hacked',
                'email' => 'super2@example.com',
                'role' => 'Super Admin',
                'is_active' => '1',
            ])
            ->assertForbidden();
    }
}
