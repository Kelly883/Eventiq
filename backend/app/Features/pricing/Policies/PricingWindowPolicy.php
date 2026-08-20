<?php

namespace App\Features\Pricing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PricingWindowPolicy
{
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
     */
    public function create(User $user): bool
    {
        // Only organizers/admins can create pricing windows
        return $user->hasRole('organizer') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update a pricing window.
     */
    public function update(User $user, $pricingWindow): bool
    {
        // Only organizers/admins can update
        return $user->hasRole('organizer') || $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can delete a pricing window.
     */
    public function delete(User $user, $pricingWindow): bool
    {
        // Only organizers/admins can delete
        return $user->hasRole('organizer') || $user->hasRole('admin') || $user->hasRole('super-admin');
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

