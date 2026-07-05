<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission($permission->name);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->isAdmin();
    }
}
