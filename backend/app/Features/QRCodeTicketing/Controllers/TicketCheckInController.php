<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Policies\CheckInPolicy;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Features\Fraud\Models\FraudEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketCheckInController extends Controller
{
    public function __construct(private readonly CheckInPolicy $checkInPolicy)
    {
    }

    /**
     * POST /api/tickets/:ticketId/check-in
     */
    public function checkIn(Request $request, string $ticketId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can perform check-ins.');
        }

        $validated = $request->validate([
            'qr_code_data' => ['nullable', 'string'],
        ]);

        $ticket = Ticket::with(['event', 'fraudEvents'])->find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Authorization: must have access to the event
        if (!$this->checkInPolicy->canAccessEvent($user, $ticket->event)) {
            return response()->json(['message' => 'You are not authorized to check in tickets for this event.'], 403);
        }

        // Check if already checked in (idempotent)
        if ($ticket->checked_in || $ticket->status === 'checked_in') {
            return response()->json([
                'success' => true,
                'message' => 'Ticket already checked in.',
                'checked_in_at' => $ticket->checked_in_at?->toDateTimeString(),
                'is_duplicate' => true,
            ]);
        }

        // Validate ticket status
        if ($ticket->status !== 'valid') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is not valid for check-in.',
                'reason' => $ticket->status === 'void' ? 'ticket_voided' : 'invalid_status',
            ], 422);
        }

        // Check for fraud flags
        $hasFraud = FraudEvent::where('ticket_id', $ticket->id)
            ->whereIn('status', ['flagged', 'auto_blocked'])
            ->exists();

        if ($hasFraud) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket blocked due to fraud investigation.',
                'reason' => 'fraud_flagged',
            ], 403);
        }

        // Perform check-in
        try {
            DB::transaction(function () use ($ticket, $user) {
                $ticket->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => now(),
                    'checked_in_by' => $user->id,
                ]);

                // Increment scan count
                $ticket->increment('qr_code_scanned_count');
                $ticket->update(['last_qr_scan_at' => now()]);

                \App\Models\AuditLog::create([
                    'action' => 'check_in',
                    'target_type' => Ticket::class,
                    'target_id' => $ticket->id,
                    'user_id' => $user->id,
                    'context' => [
                        'ticket_id' => $ticket->ticket_id,
                        'event_id' => $ticket->event_id,
                        'method' => 'api',
                    ],
                    'ip_address' => request()->ip(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Check-in failed: ' . $e->getMessage());
            return response()->json(['message' => 'Check-in failed. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in processed successfully.',
            'data' => [
                'ticket_id' => $ticket->id,
                'ticket_reference' => $ticket->ticket_id,
                'status' => 'checked_in',
                'checked_in_at' => now()->toDateTimeString(),
                'checked_in_by' => $user->id,
            ],
        ]);
    }

    /**
     * POST /api/tickets/:ticketId/void
     */
    public function void(Request $request, string $ticketId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$user->hasRole('admin') && !$user->hasRole('organizer')) {
            abort(403, 'Only organizers or admins can void tickets.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'fraud_event_id' => ['nullable', 'string'],
        ]);

        $ticket = Ticket::with(['event', 'order'])->find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        // Organizers can only void tickets for their own events
        if ($user->hasRole('organizer') && !$user->hasRole('admin')) {
            $event = $ticket->event;
            if (!$event || (string) $event->organizer->user_id !== (string) $user->id) {
                return response()->json(['message' => 'You can only void tickets for your own events.'], 403);
            }
        }

        if ($ticket->status === 'void') {
            return response()->json(['message' => 'Ticket is already void.'], 200);
        }

        try {
            DB::transaction(function () use ($ticket, $user, $validated) {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'void',
                    'refund_status' => 'voided',
                ]);

                // If there's a fraud event, update it
                if (!empty($validated['fraud_event_id'])) {
                    FraudEvent::where('id', $validated['fraud_event_id'])
                        ->update(['status' => 'confirmed_fraud']);
                }

                // Create delivery event to block future delivery
                DeliveryEvent::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $ticket->user_id,
                    'event_id' => $ticket->event_id,
                    'order_id' => $ticket->order_id,
                    'channel' => 'email',
                    'status' => 'blocked',
                    'ticket_reference' => $ticket->ticket_id ?? $ticket->id,
                    'error_message' => 'Ticket voided: ' . $validated['reason'],
                ]);

                \App\Models\AuditLog::create([
                    'action' => 'ticket_voided',
                    'target_type' => Ticket::class,
                    'target_id' => $ticket->id,
                    'user_id' => $user->id,
                    'context' => [
                        'reason' => $validated['reason'],
                        'old_status' => $oldStatus,
                        'fraud_event_id' => $validated['fraud_event_id'] ?? null,
                    ],
                    'ip_address' => request()->ip(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Void ticket failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to void ticket.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket has been voided.',
            'data' => [
                'ticket_id' => $ticket->id,
                'status' => 'void',
                'reason' => $validated['reason'],
            ],
        ]);
    }

    /**
     * GET /api/venue/check-in/sync
     */
    public function syncCheckIns(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can access check-in sync.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'last_sync_at' => ['nullable', 'date'],
        ]);

        $eventId = $validated['event_id'];
        $lastSyncAt = $validated['last_sync_at'] ?? null;

        $event = \App\Models::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['message' => 'You are not authorized to access this event.'], 403);
        }

        $query = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->with(['event', 'user']);

        if ($lastSyncAt) {
            $query->where('checked_in_at', '>', $lastSyncAt);
        }

        $checkIns = $query->orderBy('checked_in_at', 'desc')->get();

        $totalCapacity = Ticket::where('event_id', $eventId)->count();
        $totalCheckedIn = Ticket::where('event_id', $eventId)->where('status', 'checked_in')->count();
        $remainingCapacity = max(0, $totalCapacity - $totalCheckedIn);

        return response()->json([
            'data' => [
                'check_ins' => $checkIns->map(fn ($t) => [
                    'id' => $t->id,
                    'ticket_id' => $t->ticket_id,
                    'status' => $t->status,
                    'checked_in_at' => $t->checked_in_at?->toDateTimeString(),
                    'attendee_name' => $t->attendee_name,
                    'attendee_email' => $t->attendee_email,
                ]),
                'counters' => [
                    'total_capacity' => $totalCapacity,
                    'total_checked_in' => $totalCheckedIn,
                    'remaining_capacity' => $remainingCapacity,
                ],
            ],
            'synced_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * GET /api/organizer/events/:eventId/check-in-analytics
     */
    public function checkInAnalytics(Request $request, string $eventId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        // Authorization: only event owner or admin
        if (!$user->hasRole('admin')) {
            if (!$event->organizer || (string) $event->organizer->user_id !== (string) $user->id) {
                return response()->json(['message' => 'You are not authorized to view analytics for this event.'], 403);
            }
        }

        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        $query = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in');

        if ($startDate) {
            $query->where('checked_in_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('checked_in_at', '<=', $endDate);
        }

        $checkIns = $query->get();

        $totalCheckedIn = $checkIns->count();
        $totalTickets = Ticket::where('event_id', $eventId)->count();
        $checkInRate = $totalTickets > 0 ? round(($totalCheckedIn / $totalTickets) * 100, 1) : 0;

        // Group by hour for timeline
        $byHour = $checkIns->groupBy(fn ($t) => $t->checked_in_at->format('H'))
            ->map(fn ($group) => $group->count())
            ->sortKeys();

        $peakCheckInHour = $byHour->isNotEmpty() ? (int) $byHour->sortDesc()->keys()->first() : 0;

        // Group by tier for breakdown
        $byTier = $checkIns->groupBy('tier')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'percentage' => $totalCheckedIn > 0 ? round(($group->count() / $totalCheckedIn) * 100, 1) : 0,
            ]);

        // Average check-in time (minutes from event start)
        $eventStart = $event->start_datetime;
        $averageCheckInTime = $checkIns->isNotEmpty()
            ? round($checkIns->avg(fn ($t) => $eventStart ? $t->checked_in_at->diffInMinutes($eventStart) : 0))
            : 0;

        return response()->json([
            'data' => [
                'event_id' => $eventId,
                'event_title' => $event->title,
                'total_checked_in' => $totalCheckedIn,
                'total_tickets' => $totalTickets,
                'check_in_rate' => $checkInRate,
                'peak_check_in_hour' => $peakCheckInHour,
                'average_check_in_time_minutes' => $averageCheckInTime,
                'check_ins_by_hour' => $byHour,
                'check_ins_by_tier' => $byTier,
                'generated_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * POST /api/venue/check-in/bulk
     */
    public function bulkCheckIn(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can perform bulk check-ins.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'ticket_ids' => ['required', 'array', 'max:100'],
            'ticket_ids.*' => ['required', 'string'],
        ]);

        $eventId = $validated['event_id'];
        $ticketIds = $validated['ticket_ids'];

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['message' => 'You are not authorized to check in tickets for this event.'], 403);
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($ticketIds as $tid) {
            $ticket = Ticket::find($tid);

            if (!$ticket) {
                $results[] = ['ticket_id' => $tid, 'success' => false, 'message' => 'Ticket not found'];
                $failureCount++;
                continue;
            }

            if ($ticket->event_id != $eventId) {
                $results[] = ['ticket_id' => $tid, 'success' => false, 'message' => 'Ticket does not belong to this event'];
                $failureCount++;
                continue;
            }

            if ($ticket->checked_in || $ticket->status === 'checked_in') {
                $results[] = ['ticket_id' => $tid, 'success' => true, 'message' => 'Already checked in'];
                $successCount++;
                continue;
            }

            if ($ticket->status !== 'valid') {
                $results[] = ['ticket_id' => $tid, 'success' => false, 'message' => 'Ticket is not valid'];
                $failureCount++;
                continue;
            }

            try {
                $ticket->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => now(),
                    'checked_in_by' => $user->id,
                ]);
                $ticket->increment('qr_code_scanned_count');

                $results[] = ['ticket_id' => $tid, 'success' => true, 'message' => 'Checked in'];
                $successCount++;
            } catch (\Throwable $e) {
                $results[] = ['ticket_id' => $tid, 'success' => false, 'message' => 'Check-in failed'];
                $failureCount++;
            }
        }

        return response()->json([
            'data' => [
                'total_processed' => count($ticketIds),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
            ],
        ]);
    }

    /**
     * POST /api/venue/check-in/detect-duplicate
     */
    public function detectDuplicate(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can detect duplicates.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'qr_code_data' => ['required', 'string'],
            'ticket_id' => ['nullable', 'string'],
        ]);

        $eventId = $validated['event_id'];
        $qrCodeData = $validated['qr_code_data'];
        $ticketId = $validated['ticket_id'] ?? null;

        // Try to decrypt QR code
        $decrypted = null;
        try {
            $decryptedRaw = \Illuminate\Support\Facades\Crypt::decryptString($qrCodeData);
            $payload = json_decode($decryptedRaw, true);
            $decrypted = $payload;
        } catch (\Throwable $e) {
            // If decryption fails, try using ticket_id directly
            if (!$ticketId) {
                return response()->json([
                    'data' => [
                        'is_duplicate' => false,
                        'risk_level' => 'high',
                        'message' => 'Unable to decrypt QR code and no ticket ID provided.',
                    ],
                ]);
            }
        }

        $resolvedTicketId = $ticketId ?? ($decrypted['ticket_id'] ?? null);

        if (!$resolvedTicketId) {
            return response()->json([
                'data' => [
                    'is_duplicate' => false,
                    'risk_level' => 'high',
                    'message' => 'Could not determine ticket ID.',
                ],
            ]);
        }

        $ticket = Ticket::with(['fraudEvents'])->find($resolvedTicketId);

        if (!$ticket) {
            return response()->json([
                'data' => [
                    'ticket_id' => $resolvedTicketId,
                    'is_duplicate' => false,
                    'risk_level' => 'high',
                    'message' => 'Ticket not found.',
                ],
            ]);
        }

        $isDuplicate = $ticket->checked_in || $ticket->status === 'checked_in';
        $previousCheckInAt = $isDuplicate ? $ticket->checked_in_at : null;
        $previousCheckInBy = $isDuplicate ? $ticket->checked_in_by : null;

        // Check fraud events
        $fraudCount = FraudEvent::where('ticket_id', $ticket->id)->count();
        $riskLevel = $fraudCount > 0 ? 'high' : ($isDuplicate ? 'medium' : 'low');

        return response()->json([
            'data' => [
                'ticket_id' => $resolvedTicketId,
                'is_duplicate' => $isDuplicate,
                'previous_check_in_at' => $previousCheckInAt?->toDateTimeString(),
                'previous_check_in_by' => $previousCheckInBy,
                'risk_level' => $riskLevel,
                'message' => $isDuplicate
                    ? 'Ticket was already checked in at ' . ($previousCheckInAt?->toDateTimeString() ?? 'unknown')
                    : 'Ticket is valid for check-in.',
            ],
        ]);
    }

    /**
     * POST /api/venue/check-in/offline-sync
     */
    public function offlineSync(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can sync offline check-ins.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'local_check_ins' => ['required', 'array', 'max:500'],
            'local_check_ins.*.ticket_id' => ['required', 'string'],
            'local_check_ins.*.checked_in_at' => ['required', 'date'],
            'last_sync_at' => ['nullable', 'date'],
        ]);

        $eventId = $validated['event_id'];
        $localCheckIns = $validated['local_check_ins'];

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['message' => 'You are not authorized to sync check-ins for this event.'], 403);
        }

        $synced = 0;
        $conflicts = 0;
        $results = [];

        foreach ($localCheckIns as $local) {
            $ticket = Ticket::find($local['ticket_id']);

            if (!$ticket || $ticket->event_id != $eventId) {
                $results[] = ['ticket_id' => $local['ticket_id'], 'status' => 'not_found'];
                $conflicts++;
                continue;
            }

            // Check if already synced
            if ($ticket->checked_in && $ticket->checked_in_at && $ticket->checked_in_at->gt(\Carbon\Carbon::parse($local['checked_in_at']))) {
                $results[] = [
                    'ticket_id' => $local['ticket_id'],
                    'status' => 'conflict',
                    'server_checked_in_at' => $ticket->checked_in_at->toDateTimeString(),
                ];
                $conflicts++;
                continue;
            }

            try {
                $ticket->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => $local['checked_in_at'],
                    'checked_in_by' => $user->id,
                ]);
                $ticket->increment('qr_code_scanned_count');

                $results[] = ['ticket_id' => $local['ticket_id'], 'status' => 'synced'];
                $synced++;
            } catch (\Throwable $e) {
                $results[] = ['ticket_id' => $local['ticket_id'], 'status' => 'error'];
                $conflicts++;
            }
        }

        // Return current server state for cache update
        $serverCheckIns = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->get()
            ->map(fn ($t) => [
                'ticket_id' => $t->id,
                'checked_in_at' => $t->checked_in_at?->toDateTimeString(),
            ]);

        return response()->json([
            'data' => [
                'synced' => $synced,
                'conflicts' => $conflicts,
                'results' => $results,
                'server_check_ins' => $serverCheckIns,
            ],
        ]);
    }
}
