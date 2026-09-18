<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->organizer()->exists() || $user->hasRole('organizer');
    }

    public function view(User $user, Event $event): bool
    {
        $organizer = $user->organizer;
        if (!$organizer) {
            $organizer = \App\Models\Organizer::where('user_id', $user->id)->orWhere('userId', $user->id)->first();
        }
        if (!$organizer) {
            return false;
        }

        // Primary ownership check: organizer_id must match
        if ((int) $event->organizer_id !== (int) $organizer->id) {
            return false;
        }

        // Secondary check: if organizer relation is loaded and has user_id, verify it matches
        // This handles legacy user_id column mismatches
        if ($event->relationLoaded('organizer') && $event->organizer && $event->organizer->user_id !== null) {
            return (int) $user->id === (int) $event->organizer->user_id;
        }

        // If organizer relation is not loaded or user_id is null, fall back to organizer_id match
        // (already verified above)
        return true;
    }

    public function create(User $user): bool
    {
        return $user->organizer()->exists() || $user->hasRole('organizer');
    }

    public function update(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->view($user, $event);
    }
}
