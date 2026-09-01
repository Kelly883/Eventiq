<?php

namespace App\Features\CheckIn\Policies;

use App\Models\Event;
use App\Models\User;

class CheckInPolicy
{
    public function isVenueStaff(?User $user): bool
    {
        return $user !== null
            && ($user->hasRole('admin') || $user->hasRole('organizer') || $user->hasRole('venue_staff'));
    }

    public function canAccessEvent(User $user, Event $event): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('venue_staff')) {
            return $event->venueStaff()->whereKey($user->id)->exists();
        }

        return $event->organizer()->where('user_id', $user->id)->exists();
    }

    public function scopeToAccessibleEvents($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('venue_staff')) {
            return $query->whereHas('event.venueStaff', fn ($builder) => $builder->whereKey($user->id));
        }

        return $query->whereHas('event.organizer', fn ($builder) => $builder->where('user_id', $user->id));
    }
}
