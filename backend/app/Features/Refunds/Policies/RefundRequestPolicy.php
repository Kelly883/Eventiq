<?php

namespace App\Features\Refunds\Policies;

use App\Features\Refunds\Models\RefundRequest;
use App\Models\User;
use App\Features\Checkout\Models\Ticket;
use Illuminate\Auth\Access\Response;

class RefundRequestPolicy
{
    /**
     * Determine whether the user can view any refund requests (admin dashboard).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view a specific refund request.
     */
    public function view(User $user, RefundRequest $refundRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // Ticket owner can view their own refund request
        $ticket = $refundRequest->ticket;
        if (!$ticket) {
            return false;
        }

        return $ticket->user_id !== null && (string) $user->id === (string) $ticket->user_id;
    }

    /**
     * Determine whether the user can create a refund request for a ticket.
     */
    public function request(User $user, Ticket $ticket): bool
    {
        // Ticket must belong to the user, or user must be an admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Guest tickets (user_id is null) cannot be requested via policy —
        // they are handled separately via guest email verification in the service.
        return $ticket->user_id !== null && (string) $user->id === (string) $ticket->user_id;
    }

    /**
     * Determine whether the user can appeal a refund request.
     */
    public function appeal(User $user, RefundRequest $refundRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return (string) $user->id === (string) $refundRequest->user_id;
    }
}
