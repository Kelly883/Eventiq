<?php

namespace App\Features\admin\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        // Allow admin-only checks centralized.
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin');
        }

        // Fallback: if app uses `role` column.
        if (property_exists($user, 'role')) {
            return $user->role === 'admin';
        }

        return null;
    }
}

