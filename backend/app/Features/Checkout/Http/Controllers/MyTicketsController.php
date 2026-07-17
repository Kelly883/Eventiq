<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyTicketsController extends Controller
{
    /**
     * GET /api/my-tickets - all tickets for the authenticated user,
     * grouped by event.
     */
    public function index(Request $request)
    {
        $tickets = Ticket::with(['event', 'ticketTier'])
            ->where('user_id', $request->user()->id)
            ->get()
            ->groupBy('event_id')
            ->map(function ($eventTickets) {
                $event = $eventTickets->first()->event;

                return [
                    'event' => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'start_date' => $event->start_date,
                        'location' => $event->location,
                    ],
                    'tickets' => $eventTickets->map(fn ($ticket) => [
                        'id' => $ticket->id,
                        'ticket_tier' => $ticket->ticketTier->name ?? null,
                        'status' => $ticket->status,
                        'checked_in' => $ticket->checked_in,
                        'qr_code' => $ticket->qr_code,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $tickets]);
    }
}
