<?php

namespace App\Features\OrganizerProfile\Policies;

use App\Models\User;
use App\Features\OrganizerProfile\Models\OrganizerProfile;
use Illuminate\Auth\Access\Response;

class OrganizerProfilePolicy
{
    public function view(?User $user, $organizer): Response
    {
        // $organizer may be Organizer or OrganizerProfile (both map to organizers table)
        $isPublic = $organizer->isPublic ?? true;
        $ownerId = $organizer->user_id ?? $organizer->userId ?? null;

        if ($isPublic) {
            return Response::allow();
        }

        // Private: only owner can view — otherwise 404 to not reveal existence
        if ($user && $ownerId && $user->id === $ownerId) {
            return Response::allow();
        }

        return Response::denyWithStatus(404, 'Organizer not found');
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