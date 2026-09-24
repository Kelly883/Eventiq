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

        $ticket = Ticket::with(['event'])->lockForUpdate()->find($ticketId);

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
                $now = now();
                $ticket->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => $now,
                    'checked_in_by' => $user->id,
                    'last_qr_scan_at' => $now,
                    'qr_nonce' => null, // Clear nonce so QR can't be replayed
                    'sync_status' => 'synced',
                ]);

                // Increment scan count
                \Illuminate\Support\Facades\DB::table('tickets')
                    ->where('id', $ticket->id)
                    ->increment('qr_code_scanned_count');

                \App\Models\AuditLog::create([
                    'action' => 'check_in',
                    'target_type' => Ticket::class,
                    'target_id' => $ticket->id,
                    'user_id' => $user->id,
                    'metadata' => [
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
                    'metadata' => [
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

        $event = \App\Models\Event::withTrashed()->find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['message' => 'You are not authorized to access this event.'], 403);
        }

        $perPage = min((int) $request->input('per_page', 100), 500);
        $query = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->with(['event', 'user']);

        if ($lastSyncAt) {
            $query->where('checked_in_at', '>', $lastSyncAt);
        }

        $checkIns = $query->orderBy('checked_in_at', 'desc')->paginate($perPage);

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
                'pagination' => [
                    'current_page' => $checkIns->currentPage(),
                    'last_page' => $checkIns->lastPage(),
                    'per_page' => $checkIns->perPage(),
                    'total' => $checkIns->total(),
                ],
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

        $event = \App\Models\Event::withTrashed()->find($eventId);
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
     * GET /api/venue/check-in/pending-sync
     *
     * Returns tickets that still need to be synced (sync_status != 'synced').
     * Used by offline devices to discover unsynced tickets.
     */
    public function pendingSync(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can access pending sync.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max' => 500],
        ]);

        $eventId = $validated['event_id'];
        $perPage = $validated['per_page'] ?? 100;

        $event = \App\Models\Event::withTrashed()->find($eventId);
        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['message' => 'You are not authorized to access this event.'], 403);
        }

        $query = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->where('sync_status', '!=', 'synced')
            ->with(['user']);

        $pending = $query->orderBy('checked_in_at', 'desc')->paginate($perPage);

        $totalPending = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->where('sync_status', '!=', 'synced')
            ->count();

        $totalCheckedIn = Ticket::where('event_id', $eventId)
            ->where('status', 'checked_in')
            ->count();

        return response()->json([
            'data' => [
                'tickets' => $pending->map(fn ($t) => [
                    'id' => $t->id,
                    'ticket_id' => $t->ticket_id,
                    'status' => $t->status,
                    'checked_in_at' => $t->checked_in_at?->toDateTimeString(),
                    'sync_status' => $t->sync_status,
                    'attendee_name' => $t->attendee_name,
                    'attendee_email' => $t->attendee_email,
                ]),
                'pagination' => [
                    'current_page' => $pending->currentPage(),
                    'last_page' => $pending->lastPage(),
                    'per_page' => $pending->perPage(),
                    'total' => $pending->total(),
                ],
                'counters' => [
                    'total_pending' => $totalPending,
                    'total_checked_in' => $totalCheckedIn,
                ],
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

        // Deduplicate ticket IDs to avoid double-processing
        $uniqueTicketIds = array_values(array_unique($ticketIds));

        // Bulk fetch all tickets in a single query to avoid N+1
        $tickets = Ticket::whereIn('id', $uniqueTicketIds)->get()->keyBy('id');

        foreach ($ticketIds as $tid) {
            $ticket = $tickets->get($tid);

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

            $results[] = ['ticket_id' => $tid, 'success' => true, 'message' => 'Checked in', 'pending' => true];
            $successCount++;
        }

        // Perform all updates in a single transaction
        $pendingIds = collect($results)->where('pending', true)->pluck('ticket_id')->toArray();
        if (!empty($pendingIds)) {
            DB::transaction(function () use ($pendingIds, $user) {
                $now = now();
                Ticket::whereIn('id', $pendingIds)->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => $now,
                    'checked_in_by' => $user->id,
                    'sync_status' => 'synced',
                ]);

                // Increment scan counts via raw update (avoids N+1)
                \Illuminate\Support\Facades\DB::table('tickets')
                    ->whereIn('id', $pendingIds)
                    ->increment('qr_code_scanned_count');

                // Bulk create audit logs
                $auditLogs = array_map(fn ($id) => [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'action' => 'bulk_check_in',
                    'target_type' => Ticket::class,
                    'target_id' => $id,
                    'user_id' => $user->id,
                    'metadata' => json_encode(['method' => 'bulk_api']),
                    'ip_address' => request()->ip(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $pendingIds);
                \App\Models\AuditLog::insert($auditLogs);
            });
        }

        // Remove pending flag from results
        $results = array_map(function ($r) {
            unset($r['pending']);
            return $r;
        }, $results);

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
            'qr_code_data' => ['nullable', 'string'],
            'ticket_id' => ['nullable', 'string'],
        ]);

        $eventId = $validated['event_id'];
        $qrCodeData = $validated['qr_code_data'] ?? null;
        $ticketId = $validated['ticket_id'] ?? null;

        if (!$ticketId && !$qrCodeData) {
            return response()->json([
                'data' => [
                    'is_duplicate' => false,
                    'risk_level' => 'high',
                    'message' => 'Either qr_code_data or ticket_id is required.',
                ],
            ], 422);
        }
        $resolvedTicketId = $ticketId;

        if ($qrCodeData) {
            try {
                $payload = \App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::decrypt($qrCodeData);
                $resolvedTicketId = $payload ? ($payload['ticket_id'] ?? $ticketId) : $ticketId;
            } catch (\Throwable $e) {
                // Decryption failed, fall back to ticket_id
            }
        }

        if (!$resolvedTicketId) {
            return response()->json([
                'data' => [
                    'is_duplicate' => false,
                    'risk_level' => 'high',
                    'message' => 'Could not determine ticket ID.',
                ],
            ]);
        }

        $ticket = \App\Features\CheckIn\Models\Ticket::with(['event'])->find($resolvedTicketId);

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
        $fraudCount = \App\Features\Fraud\Models\FraudEvent::where('ticket_id', $ticket->id)->count();
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

        $event = \App\Models\Event::withTrashed()->find($eventId);
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
            $ticket = Ticket::where('id', $local['ticket_id'])->lockForUpdate()->first();

            if (!$ticket || $ticket->event_id != $eventId) {
                $results[] = ['ticket_id' => $local['ticket_id'], 'status' => 'not_found'];
                $conflicts++;
                continue;
            }

            // Check if already synced (with 5-minute clock-skew tolerance)
            $localCheckedInAt = \Carbon\Carbon::parse($local['checked_in_at']);
            $clockSkewTolerance = now()->subMinutes(5);
            if ($ticket->checked_in && $ticket->checked_in_at &&
                $ticket->checked_in_at->gt($localCheckedInAt) &&
                $ticket->checked_in_at->gt($clockSkewTolerance)) {
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
                    'sync_status' => 'synced',
                ]);

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
