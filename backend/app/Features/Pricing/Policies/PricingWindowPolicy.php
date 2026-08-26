<?php

namespace App\Features\Pricing\Policies;

use App\Models\User;

class PricingWindowPolicy
{
    /**
     * Admins may manage any event; organizers only their own events.
     */
    private function ownsEvent(User $user, $event): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return true;
        }

        return $event
            && $event->organizer
            && (string) $event->organizer->user_id === (string) $user->id;
    }

    /**
     * Determine whether the user can view any pricing windows.
     */
    public function viewAny(User $user): bool
    {
        // Any authenticated user can view pricing windows (for browsing events)
        return true;
    }

    /**
     * Determine whether the user can view a specific pricing window.
     */
    public function view(User $user, $pricingWindow): bool
    {
        // Any authenticated user can view
        return true;
    }

    /**
     * Determine whether the user can create pricing windows.
     * $event is the URL-resolved Event the window will belong to.
     */
    public function create(User $user, $event = null): bool
    {
        if (!$user->hasRole('organizer') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->ownsEvent($user, $event);
    }

    /**
     * Determine whether the user can update a pricing window.
     */
    public function update(User $user, $pricingWindow): bool
    {
        if (!$user->hasRole('organizer') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->ownsEvent($user, $pricingWindow->event);
    }

    /**
     * Determine whether the user can delete a pricing window.
     */
    public function delete(User $user, $pricingWindow): bool
    {
        if (!$user->hasRole('organizer') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        return $this->ownsEvent($user, $pricingWindow->event);
    }

    /**
     * Determine whether the user can restore a soft-deleted pricing window.
     */
    public function restore(User $user, $pricingWindow): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can permanently delete a pricing window.
     */
    public function forceDelete(User $user, $pricingWindow): bool
    {
        return $user->hasRole('super-admin');
    }
}
