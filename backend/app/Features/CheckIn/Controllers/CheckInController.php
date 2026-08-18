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
     * List tickets for a given event with optional filters.
     *
     * GET /api/venue/check-ins
     */
    public function index(Request $request)
    {
        $eventId = $request->query('event_id');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = CheckInTicket::query()->with(['event', 'user', 'fraudEvents']);

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
    public function show(CheckInTicket $ticket)
    {
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
        $validated = $request->validate([
            'ticket_code' => ['required', 'string'],
            'event_id' => ['required', 'string'],
            'scanned_at' => ['nullable', 'string'],
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

        $ticket = CheckInTicket::where('ticket_id', $ticketCode)
            ->orWhere('qr_code_data', $ticketCode)
            ->orWhere('id', $ticketCode)
            ->where('event_id', $eventId)
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
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1'],
            'event_id' => ['nullable', 'string'],
        ]);

        $query = CheckInTicket::query()->with(['event', 'user', 'fraudEvents']);

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
