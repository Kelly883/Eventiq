<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiKeyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('organizer');
    }

    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('admin') || $user->organizer_id === $apiKey->organizer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('organizer');
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('admin') || $user->organizer_id === $apiKey->organizer_id;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('admin') || $user->organizer_id === $apiKey->organizer_id;
    }

    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $user->hasRole('admin') || $user->organizer_id === $apiKey->organizer_id;
    }
}
