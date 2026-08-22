<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebhookPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'admin' || $user->role?->name === 'organizer';
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->role?->name === 'admin' || $user->organizer_id === $webhook->organizer_id;
    }

    public function create(User $user): bool
    {
        return $user->role?->name === 'admin' || $user->role?->name === 'organizer';
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->role?->name === 'admin' || $user->organizer_id === $webhook->organizer_id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->role?->name === 'admin' || $user->organizer_id === $webhook->organizer_id;
    }
}
