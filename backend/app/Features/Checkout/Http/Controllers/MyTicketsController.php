<?php

namespace App\Features\Checkout\Http\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Features\Dashboard\Models\UserDashboardPreference;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MyTicketsController extends Controller
{
    /**
     * GET /api/my-tickets
     */
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
        $page = $validated['page'] ?? 1;

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

        $total = $query->count();
        $tickets = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

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
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'filter' => $filter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * GET /api/users/me/dashboard-overview
     */
    public function dashboardOverview(Request $request)
    {
        $user = $request->user();

        $totalTickets = Ticket::where('user_id', $user->id)->count();
        $upcomingTickets = Ticket::where('user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('start_datetime', '>=', now()))
            ->count();
        $pastTickets = Ticket::where('user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('end_datetime', '<', now()))
            ->count();
        $checkedIn = Ticket::where('user_id', $user->id)->where('checked_in', true)->count();

        $nextUpcomingEvent = Ticket::where('user_id', $user->id)
            ->whereHas('event', fn ($q) => $q->where('start_datetime', '>=', now()))
            ->with('event')
            ->orderBy('created_at', 'desc')
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
                'totalTickets' => $totalTickets,
                'upcomingTickets' => $upcomingTickets,
                'pastTickets' => $pastTickets,
                'checkedIn' => $checkedIn,
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

    /**
     * GET /api/users/me/dashboard-preferences
     */
    public function dashboardPreferences(Request $request)
    {
        try {
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
        } catch (\Throwable) {
            return response()->json([
                'data' => [
                    'default_ticket_filter' => 'all',
                    'default_date_range' => '30days',
                    'show_recommendations' => true,
                    'show_activity_feed' => true,
                    'auto_refresh_enabled' => true,
                ],
            ]);
        }
    }

    /**
     * PATCH /api/users/me/dashboard-preferences
     */
    public function updateDashboardPreferences(Request $request)
    {
        $validated = $request->validate([
            'default_ticket_filter' => ['nullable', 'string', 'in:all,upcoming,past'],
            'default_date_range' => ['nullable', 'string', 'in:7days,30days,90days,all'],
            'show_recommendations' => ['nullable', 'boolean'],
            'show_activity_feed' => ['nullable', 'boolean'],
            'auto_refresh_enabled' => ['nullable', 'boolean'],
        ]);

        $prefs = UserDashboardPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'default_ticket_filter' => 'all',
                'default_date_range' => '30days',
                'show_recommendations' => true,
                'show_activity_feed' => true,
                'auto_refresh_enabled' => true,
            ]
        );

        $updateData = [];
        if (array_key_exists('default_ticket_filter', $validated)) {
            $updateData['default_ticket_filter'] = $validated['default_ticket_filter'];
        }
        if (array_key_exists('default_date_range', $validated)) {
            $updateData['default_date_range'] = $validated['default_date_range'];
        }
        if (array_key_exists('show_recommendations', $validated)) {
            $updateData['show_recommendations'] = (int) $validated['show_recommendations'];
        }
        if (array_key_exists('show_activity_feed', $validated)) {
            $updateData['show_activity_feed'] = (int) $validated['show_activity_feed'];
        }
        if (array_key_exists('auto_refresh_enabled', $validated)) {
            $updateData['auto_refresh_enabled'] = (int) $validated['auto_refresh_enabled'];
        }

        if (!empty($updateData)) {
            $prefs->update($updateData);
        }

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

    /**
     * GET /api/tickets/:ticketId/details
     */
    public function ticketDetails(Request $request, string $ticketId)
    {
        $ticket = Ticket::with(['event', 'ticketTier'])
            ->whereKey($ticketId)
            ->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if (!($ticket->user_id === $request->user()?->id || $request->user()?->hasRole('admin'))) {
            return response()->json(['message' => 'You do not have access to this ticket.'], 403);
        }

        $deliveryHistory = DeliveryEvent::where('ticket_id', $ticket->id)
            ->orderByDesc('created_at')
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
