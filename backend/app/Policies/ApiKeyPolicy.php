<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApiKeyPolicy
{
    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->organizer && $apiKey->organizer_id === $user->organizer->id;
    }

    public function revoke(User $user, ApiKey $apiKey): bool
    {
        return $user->organizer && $apiKey->organizer_id === $user->organizer->id;
    }
}
