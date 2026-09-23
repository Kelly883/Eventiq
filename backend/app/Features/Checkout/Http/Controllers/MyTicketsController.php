<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Features\Dashboard\Models\UserDashboardPreference;
use App\Features\Tickets\Policies\TicketPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyTicketsController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'string', 'in:upcoming,past,all'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filter = $validated['filter'] ?? 'all';
        $search = $validated['search'] ?? null;
        $perPage = $validated['per_page'] ?? 20;

        $query = Ticket::with(['event', 'ticketTier'])
            ->where('user_id', $request->user()->id);

        if ($filter === 'upcoming') {
            $query->whereHas('event', fn ($q) => $q->where('start_datetime', '>=', now()));
        } elseif ($filter === 'past') {
            $query->whereHas('event', fn ($q) => $q->where('end_datetime', '<', now()));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_id', 'like', "%{$search}%")
                    ->orWhereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$search}%"));
            });
        }

        $query->orderBy('created_at', 'desc');

        $tickets = $query->paginate($perPage);

        return response()->json([
            'data' => $tickets->map(fn (Ticket $t) => [
                'id' => $t->id,
                'ticket_id' => $t->ticket_id,
                'status' => $t->status,
                'tier_name' => $t->ticketTier->name ?? null,
                'event_name' => $t->event->title ?? null,
                'event_start' => $t->event->start_datetime?->toDateTimeString(),
                'event_venue' => $t->event->venue_name ?? null,
                'checked_in' => (bool) $t->checked_in,
                'created_at' => $t->created_at?->toDateTimeString(),
            ]),
            'meta' => [
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'filter' => $filter,
                'search' => $search,
            ],
        ]);
    }

    public function dashboardOverview(Request $request)
    {
        $user = $request->user();

        // Single query with conditional aggregates for counts
        $stats = Ticket::where('tickets.user_id', $user->id)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN tickets.checked_in = 1 THEN 1 ELSE 0 END) as checked_in')
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM events WHERE events.id = tickets.event_id AND events.start_datetime >= ?) THEN 1 ELSE 0 END) as upcoming', [now()])
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM events WHERE events.id = tickets.event_id AND events.end_datetime < ?) THEN 1 ELSE 0 END) as past', [now()])
            ->first();

        // nextUpcomingEvent: order by event start_datetime ASC (first upcoming event)
        $nextUpcomingEvent = Ticket::where('tickets.user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('start_datetime', '>=', now()))
            ->with('event')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->orderBy('events.start_datetime', 'asc')
            ->select('tickets.*')
            ->first();

        $recentActivity = Ticket::where('user_id', $user->id)
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'event_name' => $t->event->title ?? null,
                'ticket_id' => $t->ticket_id,
                'status' => $t->status,
                'purchased_at' => $t->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'data' => [
                'totalTickets' => (int) $stats->total,
                'upcomingTickets' => (int) $stats->upcoming,
                'pastTickets' => (int) $stats->past,
                'checkedIn' => (int) $stats->checked_in,
                'nextUpcomingEvent' => $nextUpcomingEvent ? [
                    'id' => $nextUpcomingEvent->event->id,
                    'title' => $nextUpcomingEvent->event->title,
                    'start_datetime' => $nextUpcomingEvent->event->start_datetime?->toDateTimeString(),
                    'venue' => $nextUpcomingEvent->event->venue_name,
                ] : null,
                'recentActivity' => $recentActivity,
            ],
        ]);
    }

    public function dashboardPreferences(Request $request)
    {
        $prefs = UserDashboardPreference::firstOrCreateForUser($request->user());

        return response()->json([
            'data' => [
                'default_ticket_filter' => $prefs->default_ticket_filter,
                'default_date_range' => $prefs->default_date_range,
                'show_recommendations' => (bool) $prefs->show_recommendations,
                'show_activity_feed' => (bool) $prefs->show_activity_feed,
                'auto_refresh_enabled' => (bool) $prefs->auto_refresh_enabled,
            ],
        ]);
    }

    public function updateDashboardPreferences(Request $request)
    {
        $validated = $request->validate([
            'default_ticket_filter' => ['nullable', 'string', 'in:all,upcoming,past'],
            'default_date_range' => ['nullable', 'string', 'in:7days,30days,90days,all'],
            'show_recommendations' => ['nullable', 'boolean'],
            'show_activity_feed' => ['nullable', 'boolean'],
            'auto_refresh_enabled' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $prefs = UserDashboardPreference::updatePreferences($user, $validated);

        return response()->json([
            'data' => [
                'default_ticket_filter' => $prefs->default_ticket_filter,
                'default_date_range' => $prefs->default_date_range,
                'show_recommendations' => (bool) $prefs->show_recommendations,
                'show_activity_feed' => (bool) $prefs->show_activity_feed,
                'auto_refresh_enabled' => (bool) $prefs->auto_refresh_enabled,
            ],
        ]);
    }

    public function ticketDetails(Request $request, string $ticketId)
    {
        $ticket = Ticket::with(['event', 'ticketTier'])
            ->whereKey($ticketId)
            ->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $this->authorize('view', $ticket);

        $deliveryHistory = DeliveryEvent::where('ticket_id', $ticket->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (DeliveryEvent $d) => [
                'id' => $d->id,
                'channel' => $d->channel,
                'status' => $d->status,
                'recipient' => $d->recipient,
                'attempt_count' => $d->attempt_count,
                'max_attempts' => $d->max_attempts,
                'last_attempt_at' => $d->last_attempt_at?->toDateTimeString(),
                'delivered_at' => $d->delivered_at?->toDateTimeString(),
                'next_retry_at' => $d->next_retry_at?->toDateTimeString(),
                'created_at' => $d->created_at?->toDateTimeString(),
            ]);

        return response()->json([
            'data' => [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'status' => $ticket->status,
                'tier_name' => $ticket->ticketTier->name ?? null,
                'attendee_name' => $ticket->attendee_name,
                'attendee_email' => $ticket->attendee_email,
                'qr_code_data' => $ticket->qr_code_data,
                'checked_in' => (bool) $ticket->checked_in,
                'checked_in_at' => $ticket->checked_in_at?->toDateTimeString(),
                'created_at' => $ticket->created_at?->toDateTimeString(),
                'event' => $ticket->event ? [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'start_datetime' => $ticket->event->start_datetime?->toDateTimeString(),
                    'end_datetime' => $ticket->event->end_datetime?->toDateTimeString(),
                    'venue_name' => $ticket->event->venue_name,
                    'venue_address' => $ticket->event->venue_address,
                ] : null,
                'delivery_history' => $deliveryHistory,
            ],
        ]);
    }
}
