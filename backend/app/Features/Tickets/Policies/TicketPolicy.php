<?php

namespace App\Features\Tickets\Policies;

use App\Features\Checkout\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine if the user can view the ticket's delivery status.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the ticket (e.g., resend delivery).
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $user->hasRole('admin');
    }
}
