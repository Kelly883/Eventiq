<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MyTicketsController extends Controller
{
    /**
     * GET /api/my-tickets - all tickets for the authenticated user,
     * grouped by event.
     *
     * Uses cursor-based pagination to handle large result sets efficiently.
     */
    public function index(Request $request)
    {
        $perPage = min((int) ($request->input('per_page', 50)), 100); // Max 100 per page
        $page = (int) ($request->input('page', 1));

        $query = Ticket::with(['event', 'ticketTier'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Get total count for pagination
        $total = $query->count();

        // Fetch paginated results
        $tickets = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Group by event
        $grouped = $tickets->groupBy('event_id')->map(function ($eventTickets) {
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
                    'qr_code' => $ticket->qr_code_data,
                ])->values(),
            ];
        })->values();

        // Create paginator
        $paginator = new LengthAwarePaginator(
            $grouped,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
