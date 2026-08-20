<?php

namespace App\Features\OrganizerProfile\Policies;

use App\Models\User;
use App\Features\OrganizerProfile\Models\OrganizerProfile;
use Illuminate\Auth\Access\Response;

class OrganizerProfilePolicy
{
    public function view(?User $user, OrganizerProfile $organizer): Response
    {
        return Response::allow();
    }

    public function update(User $user, OrganizerProfile $organizer): Response
    {
        return $user->id === $organizer->user_id
            ? Response::allow()
            : Response::deny('You do not own this organizer profile.');
    }

    public function viewAuditLog(User $user, OrganizerProfile $organizer): Response
    {
        return $user->id === $organizer->user_id
            ? Response::allow()
            : Response::deny();
    }
}