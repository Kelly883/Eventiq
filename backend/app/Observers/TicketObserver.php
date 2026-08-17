<?php

namespace App\Observers;

use App\Features\Checkout\Models\Ticket;
use App\Models\Organizer;
use App\Models\Event;

class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        $organizerId = Event::where('id', $ticket->event_id)->value('organizer_id');

        if ($organizerId) {
            Organizer::where('id', $organizerId)->increment('totalTicketsSold');
        }
    }

    public function deleting(Ticket $ticket): void
    {
        $organizerId = Event::where('id', $ticket->event_id)->value('organizer_id');

        if ($organizerId) {
            Organizer::where('id', $organizerId)->decrement('totalTicketsSold');
        }
    }
}
