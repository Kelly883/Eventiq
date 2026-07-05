<?php

namespace App\Policies;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizerPolicy
{
    public function view(User $user, Organizer $organizer)
    {
        // Public or owner
    }

    public function update(User $user, Organizer $organizer)
    {
        return $user->id === $organizer->user_id;
    }
}
