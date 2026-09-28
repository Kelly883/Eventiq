<?php

namespace App\Observers;

use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use App\Models\Organizer;
use App\Services\AdminDashboardCacheService;
use Illuminate\Support\Facades\Cache;

class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        $organizerId = Event::where('id', $ticket->event_id)->value('organizer_id');

        if ($organizerId) {
            Organizer::where('id', $organizerId)->increment('totalTicketsSold');
        }

        AdminDashboardCacheService::invalidateOverview();
    }

    public function deleting(Ticket $ticket): void
    {
        $organizerId = Event::where('id', $ticket->event_id)->value('organizer_id');

        if ($organizerId) {
            Organizer::where('id', $organizerId)->decrement('totalTicketsSold');
        }

        AdminDashboardCacheService::invalidateOverview();
    }

    public function updated(Ticket $ticket): void
    {
        if ($ticket->isDirty('checked_in')) {
            Cache::forget('dashboard-overview:' . $ticket->user_id);
        }

        AdminDashboardCacheService::invalidateOverview();
    }
}
