<?php

namespace App\Features\Payment\Policies;

use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function manage(User $user, Organizer $organizer): bool
    {
        // TODO: implement authorization.
        return true;
    }
}

