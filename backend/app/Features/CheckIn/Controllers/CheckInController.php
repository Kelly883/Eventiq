<?php

namespace App\Features\CheckIn\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\CheckIn\Models\Ticket as CheckInTicket;
use App\Features\CheckIn\Models\AuditLog;
use App\Features\CheckIn\Models\FraudEvent;
use App\Features\CheckIn\Http\Resources\TicketResource;
use App\Features\CheckIn\Http\Resources\AuditLogResource;
use App\Features\CheckIn\Http\Resources\FraudEventResource;

class CheckInController extends Controller
{
    /**
     * Only admins and organizers (venue staff) may access check-in data.
     */
    private function authorizeVenueStaff($user): void
    {
        if (!$user || !($user->hasRole('admin') || $user->hasRole('organizer'))) {
            abort(403, 'Only venue staff can perform this action.');
        }
    }

    /**
     * Scope a ticket query to events owned by the authenticated organizer
     * (admins see everything).
     */
    private function scopeToAccessibleEvents($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->whereHas('event.organizer', fn ($q) => $q->where('user_id', $user->id));
    }

    /**
     * Determine whether the user may act on the given ticket's event.
     */
    private function canAccessTicket($user, $ticket): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return CheckInTicket::where('id', $ticket->id)
            ->whereHas('event.organizer', fn ($q) => $q->where('user_id', $user->id))
            ->exists();
    }

    /**
     * List tickets for a given event with optional filters.
     *
     * GET /api/venue/check-ins
     */
    public function index(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = CheckInTicket::query()->with(['event', 'user', 'fraudEvents']);
        $query = $this->scopeToAccessibleEvents($query, $request->user());

        if ($eventId) {
            $query->byEvent($eventId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_id', 'like', "%{$search}%")
                  ->orWhere('attendee_name', 'like', "%{$search}%")
                  ->orWhere('attendee_email', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        return TicketResource::collection($tickets);
    }

    /**
     * Show a specific ticket with its fraud events and audit logs.
     *
     * GET /api/venue/check-ins/{ticket}
     */
    public function show(Request $request, CheckInTicket $ticket)
    {
        $this->authorizeVenueStaff($request->user());

        if (!$this->canAccessTicket($request->user(), $ticket)) {
            abort(403, 'You are not authorized to view this ticket.');
        }

        $ticket->loadMissing(['event', 'user', 'fraudEvents']);

        return new TicketResource($ticket);
    }

    /**
     * Check in a ticket by QR code or ticket ID.
     *
     * POST /api/venue/check-in
     */
    public function store(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $validated = $request->validate([
            'ticket_code' => ['required', 'string'],
            'event_id' => ['required', 'string'],
            'scanned_at' => ['nullable', 'date'],
            'client_mutation_id' => ['nullable', 'string'],
        ]);

        $ticketCode = $validated['ticket_code'];
        $eventId = $validated['event_id'];
        $scannedAt = $validated['scanned_at'] ?? now()->toIso8601String();
        $clientMutationId = $validated['client_mutation_id'] ?? null;

        if ($clientMutationId) {
            $existingCheckIn = AuditLog::where('action', 'check_in')
                ->where('details->client_mutation_id', $clientMutationId)
                ->first();

            if ($existingCheckIn) {
                return response()->json([
                    'success' => true,
                    'message' => 'Check-in already processed (idempotent duplicate).',
                    'id' => $existingCheckIn->id,
                    'ticket_id' => $existingCheckIn->ticket_id,
                    'is_duplicate' => true,
                ]);
            }
        }

        // Wrap the orWhere chain in a closure so the event scope binds to every branch,
        // and scope to events the caller owns (admins exempt).
        $ticket = CheckInTicket::where(function ($q) use ($ticketCode) {
            $q->where('ticket_id', $ticketCode)
              ->orWhere('qr_code_data', $ticketCode)
              ->orWhere('id', $ticketCode);
        })
            ->where('event_id', $eventId)
            ->when(
                !$request->user()->hasRole('admin'),
                fn ($q) => $q->whereHas('event.organizer', fn ($o) => $o->where('user_id', $request->user()->id))
            )
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found or invalid.',
            ], 404);
        }

        if (!$ticket->canCheckIn()) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket cannot be checked in.',
                'reason' => $ticket->status === 'checked_in' ? 'already_checked_in' : 'invalid_status',
                'checked_in_at' => $ticket->checked_in_at,
            ], 422);
        }

        $ticket->status = 'checked_in';
        $ticket->checked_in_at = $scannedAt;
        $ticket->checked_in_by = $request->user()->id ?? null;
        $ticket->save();

        AuditLog::create([
            'event_id' => $eventId,
            'user_id' => $request->user()->id ?? null,
            'action' => 'check_in',
            'ticket_id' => $ticket->id,
            'details' => array_filter([
                'ticket_id' => $ticket->ticket_id,
                'attendee_name' => $ticket->attendee_name,
                'scanned_at' => $scannedAt,
                'client_mutation_id' => $clientMutationId,
            ]),
        ]);

        $ticket->loadMissing(['event', 'user', 'fraudEvents']);

        return response()->json([
            'success' => true,
            'message' => 'Check-in processed successfully.',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Search tickets by query string.
     *
     * GET /api/venue/check-ins/search
     */
    public function search(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'event_id' => ['nullable', 'string'],
        ]);

        $query = CheckInTicket::query()->with(['event', 'user', 'fraudEvents']);
        $query = $this->scopeToAccessibleEvents($query, $request->user());

        if ($validated['event_id'] ?? null) {
            $query->byEvent($validated['event_id']);
        }

        $search = $validated['q'];
        $query->where(function ($q) use ($search) {
            $q->where('ticket_id', 'like', "%{$search}%")
              ->orWhere('attendee_name', 'like', "%{$search}%")
              ->orWhere('attendee_email', 'like', "%{$search}%");
        });

        $results = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'results' => TicketResource::collection($results),
            'total' => $results->count(),
            'query' => $search,
        ]);
    }
}
