<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
{
    public function view(User $user, Webhook $webhook): bool
    {
        return $webhook->organizer_id === $user->id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $webhook->organizer_id === $user->id;
    }
}
