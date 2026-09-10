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
        $ownerOrganizerId = $event->organizer_id;
        if ($ownerOrganizerId != $organizer->id) {
            if (isset($event->user_id) && $event->user_id === $user->id) {
                return true;
            }
            return false;
        }
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
