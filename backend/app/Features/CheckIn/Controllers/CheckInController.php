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
use App\Features\CheckIn\Policies\CheckInPolicy;

class CheckInController extends Controller
{
    public function __construct(private readonly CheckInPolicy $checkInPolicy)
    {
    }

    private function authorizeVenueStaff($user): void
    {
        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can perform this action.');
        }
    }

    /**
     * Scope a ticket query to events owned by the authenticated organizer
     * (admins see everything).
     */
    private function scopeToAccessibleEvents($query, $user)
    {
        return $this->checkInPolicy->scopeToAccessibleEvents($query, $user);
    }

    /**
     * Determine whether the user may act on the given ticket's event.
     */
    private function canAccessTicket($user, $ticket): bool
    {
        return $this->checkInPolicy->scopeToAccessibleEvents(
            CheckInTicket::where('id', $ticket->id),
            $user
        )->exists();
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

        // Wrap the orWhere chain in a closure so the event scope binds to every branch.
        $ticketQuery = CheckInTicket::where(function ($q) use ($ticketCode) {
            $q->where('ticket_id', $ticketCode)
              ->orWhere('qr_code_data', $ticketCode)
              ->orWhere('id', $ticketCode);
        })
            ->where('event_id', $eventId);
        $ticket = $this->checkInPolicy
            ->scopeToAccessibleEvents($ticketQuery, $request->user())
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

    /**

    /**
     * Real-time check-in statistics for an event.
     *
     * GET /api/venue/check-ins/stats
     */
    public function stats(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $eventId = $request->query('event_id');

        $query = CheckInTicket::query();
        $query = $this->scopeToAccessibleEvents($query, $request->user());

        if ($eventId) {
            $query->byEvent($eventId);
        }

        $total = (clone $query)->count();
        $checkedIn = (clone $query)->where('status', 'checked_in')->count();
        $remaining = max(0, $total - $checkedIn);
        $rate = $total > 0 ? round(($checkedIn / $total) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'checked_in' => $checkedIn,
                'remaining' => $remaining,
                'rate' => $rate,
            ],
        ]);
    }

    /**
     * Export check-in records as CSV or JSON.
     *
     * GET /api/venue/check-ins/export
     */
    public function export(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $eventId = $request->query('event_id');
        $format = $request->query('format', 'csv');

        if (!in_array($format, ['csv', 'json'])) {
            return response()->json(['success' => false, 'message' => 'Invalid format. Use csv or json.'], 422);
        }

        $query = CheckInTicket::query()->with(['event', 'user']);
        $query = $this->scopeToAccessibleEvents($query, $request->user());

        if ($eventId) {
            $query->byEvent($eventId);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => TicketResource::collection($tickets),
                'exported_at' => now()->toIso8601String(),
            ]);
        }

        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="check-in-export.csv"',
        ];

        $callback = function () use ($tickets) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ticket_id', 'attendee_name', 'attendee_email',
                'event_name', 'status', 'checked_in_at',
                'checked_in_by', 'created_at',
            ]);
            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->ticket_id,
                    $ticket->attendee_name ?? '',
                    $ticket->attendee_email ?? '',
                    $ticket->event?->name ?? '',
                    $ticket->status,
                    $ticket->checked_in_at ?? '',
                    $ticket->checked_in_by ?? '',
                    $ticket->created_at?->toIso8601String() ?? '',
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Audit log of all check-in actions.
     *
     * GET /api/venue/check-ins/history
     */
    public function history(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $eventId = $request->query('event_id');
        $perPage = min((int) $request->query('per_page', 50), 200);

        $query = AuditLog::query()
            ->with(['user'])
            ->where('action', '!=', 'fraud_suspected');

        $query = $this->checkInPolicy->scopeToAccessibleEvents($query, $request->user());

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}
