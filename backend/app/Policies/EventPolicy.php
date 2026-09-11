<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
        // Strict organizer ownership — no fallback via event->user_id to prevent
        // bypass where a stale user_id on event could grant access to non-owner.
        return (int) $event->organizer_id === (int) $organizer->id;
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
