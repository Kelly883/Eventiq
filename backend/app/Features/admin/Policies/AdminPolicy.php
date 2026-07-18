<?php

namespace App\Features\admin\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): bool|null
    {
        // Centralized admin-only check. The property_exists() fallback
        // this used to have for a hypothetical `role` column was dead
        // code - Eloquent model attributes aren't real PHP properties
        // (they live in the magic $attributes array), so that branch
        // could never actually fire. hasRole() is the model's real,
        // working mechanism.
        return $user->hasRole('admin');
    }
}

