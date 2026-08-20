<?php

namespace App\Features\Payment\Policies;

use App\Models\Organizer;
use App\Models\User;

class OrganizerPolicy
{
    public function manage(User $user, Organizer $organizer): bool
    {
        return (string) $organizer->user_id === (string) $user->id
            || $user->hasRole('admin');
    }

    public function viewPaymentSettings(User $user, Organizer $organizer): bool
    {
        return $this->manage($user, $organizer);
    }

    public function updatePaymentSettings(User $user, Organizer $organizer): bool
    {
        return $this->manage($user, $organizer);
    }
}
