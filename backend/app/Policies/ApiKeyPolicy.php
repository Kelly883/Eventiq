<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\Organizer;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user, Organizer $organizer): bool
    {
        return $user->id === $organizer->user_id;
    }

    public function create(User $user, Organizer $organizer): bool
    {
        return $user->id === $organizer->user_id;
    }

    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $apiKey->organizer?->user_id === $user->id;
    }
}
