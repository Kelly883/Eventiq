<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Organizer;

class OrganizerObserver
{
    public function created(Event $event): void
    {
        if ($event->organizer_id) {
            Organizer::where('id', $event->organizer_id)->increment('totalEventsCreated');
        }
    }

    public function deleted(Event $event): void
    {
        if ($event->organizer_id) {
            Organizer::where('id', $event->organizer_id)->decrement('totalEventsCreated');
        }
    }

    public function restored(Event $event): void
    {
        if ($event->organizer_id) {
            Organizer::where('id', $event->organizer_id)->increment('totalEventsCreated');
        }
    }
}
