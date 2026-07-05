<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    public function viewAny(User $user)
    {
        //
    }

    public function view(User $user, Event $event)
    {
        //
    }

    public function create(User $user)
    {
        // Must be organizer
    }

    public function update(User $user, Event $event)
    {
        return $user->id === $event->organizer->user_id;
    }

    public function delete(User $user, Event $event)
    {
        return $user->id === $event->organizer->user_id;
    }
}
