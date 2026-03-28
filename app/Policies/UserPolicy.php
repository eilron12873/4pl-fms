<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public const ROLE_SUPER_ADMIN = 'Super Admin';

    public const ROLE_ADMIN = 'Admin';

    public function viewAny(User $user): bool
    {
        return $user->can('lfs-administration.users.view')
            && ($user->hasRole(self::ROLE_SUPER_ADMIN) || $user->hasRole(self::ROLE_ADMIN));
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('lfs-administration.users.manage')
            && ($user->hasRole(self::ROLE_SUPER_ADMIN) || $user->hasRole(self::ROLE_ADMIN));
    }

    public function update(User $user, User $model): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($model->hasRole(self::ROLE_SUPER_ADMIN) && ! $user->hasRole(self::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function toggleActive(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function assignRoleName(User $user, string $roleName): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($roleName === self::ROLE_SUPER_ADMIN) {
            return $user->hasRole(self::ROLE_SUPER_ADMIN);
        }

        return true;
    }

    public static function superAdminCount(): int
    {
        return User::role(self::ROLE_SUPER_ADMIN)->count();
    }

    public static function wouldRemoveLastSuperAdmin(User $target, string $newRoleName): bool
    {
        if (! $target->hasRole(self::ROLE_SUPER_ADMIN)) {
            return false;
        }

        if ($newRoleName === self::ROLE_SUPER_ADMIN) {
            return false;
        }

        return self::superAdminCount() <= 1;
    }

    public static function wouldDeleteLastSuperAdmin(User $target): bool
    {
        if (! $target->hasRole(self::ROLE_SUPER_ADMIN)) {
            return false;
        }

        return self::superAdminCount() <= 1;
    }
}
