<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Policies\CheckInPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VenueCheckInController extends Controller
{
    public function __construct(private readonly CheckInPolicy $checkInPolicy)
    {
    }

    /**
     * POST /api/venue/check-in/manual
     */
    public function manualCheckIn(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can perform manual check-ins.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'uuid'],
            'ticket_id' => ['required', 'string'],
        ]);

        $event = \App\Models\Event::find($validated['event_id']);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this event.'], 403);
        }

        $ticket = Ticket::where('event_id', $validated['event_id'])
            ->where('ticket_id', $validated['ticket_id'])
            ->first();

        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found.'], 404);
        }

        if ($ticket->status === 'checked_in' || $ticket->checked_in) {
            return response()->json([
                'success' => true,
                'message' => 'Ticket already checked in.',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_reference' => $ticket->ticket_id,
                    'status' => 'checked_in',
                    'checked_in_at' => $ticket->checked_in_at?->toDateTimeString(),
                    'is_duplicate' => true,
                ],
            ]);
        }

        if ($ticket->status !== 'valid') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is not valid for check-in.',
                'data' => ['ticket_id' => $ticket->id, 'status' => $ticket->status],
            ], 422);
        }

        try {
            DB::transaction(function () use ($ticket, $user) {
                $ticket->update([
                    'status' => 'checked_in',
                    'checked_in' => true,
                    'checked_in_at' => now(),
                    'checked_in_by' => $user->id,
                ]);
                $ticket->increment('qr_code_scanned_count');
                $ticket->update(['last_qr_scan_at' => now()]);

                \App\Models\AuditLog::create([
                    'action' => 'manual_check_in',
                    'target_type' => Ticket::class,
                    'target_id' => $ticket->id,
                    'user_id' => $user->id,
                    'context' => [
                        'ticket_id' => $ticket->ticket_id,
                        'event_id' => $ticket->event_id,
                        'method' => 'manual',
                    ],
                    'ip_address' => request()->ip(),
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Manual check-in failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Check-in failed. Please try again.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Manual check-in processed successfully.',
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
     * GET /api/venue/check-in/search
     */
    public function search(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can search check-ins.');
        }

        $validated = $request->validate([
            'event_id' => ['required', 'uuid'],
            'query' => ['required', 'string', 'min:2'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $eventId = $validated['event_id'];
        $query = $validated['query'];
        $limit = $validated['limit'] ?? 10;

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this event.'], 403);
        }

        $results = Ticket::where('event_id', $eventId)
            ->where(function ($q) use ($query) {
                $q->where('ticket_id', $query)
                    ->orWhere('attendee_email', 'LIKE', "%{$query}%")
                    ->orWhere('attendee_name', 'LIKE', "%{$query}%");
            })
            ->orderBy('checked_in_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => [
                'ticket_id' => $t->id,
                'ticket_reference' => $t->ticket_id,
                'attendee_name' => $t->attendee_name,
                'attendee_email' => $t->attendee_email,
                'status' => $t->status,
                'checked_in_at' => $t->checked_in_at?->toDateTimeString(),
                'checked_in_by' => $t->checked_in_by,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'results_count' => $results->count(),
                'results' => $results,
            ],
        ]);
    }

    /**
     * GET /api/venue/check-in/stats/{event}
     */
    public function stats(Request $request, string $eventId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can view check-in stats.');
        }

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this event.'], 403);
        }

        $lastUpdateAt = $request->query('lastUpdateAt');

        $baseQuery = Ticket::where('event_id', $eventId);

        if ($lastUpdateAt) {
            $baseQuery->where('checked_in_at', '>', $lastUpdateAt);
        }

        $totalCapacity = (clone $baseQuery)->count();
        $totalCheckedIn = (clone $baseQuery)->where('status', 'checked_in')->count();
        $totalVoid = (clone $baseQuery)->where('status', 'void')->count();
        $totalRemaining = max(0, $totalCapacity - $totalCheckedIn);
        $checkInRate = $totalCapacity > 0 ? round(($totalCheckedIn / $totalCapacity) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'event_id' => $eventId,
                'total_capacity' => $totalCapacity,
                'total_checked_in' => $totalCheckedIn,
                'total_remaining' => $totalRemaining,
                'total_void' => $totalVoid,
                'check_in_rate' => $checkInRate,
                'last_update_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * GET /api/venue/check-in/export/{event}
     */
    public function export(Request $request, string $eventId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff can export check-ins.');
        }

        $validated = $request->validate([
            'format' => ['nullable', 'in:csv,json'],
            'include_no_shows' => ['nullable', 'boolean'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
        ]);

        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Event not found.'], 404);
        }

        if (!$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to access this event.'], 403);
        }

        $format = $validated['format'] ?? 'csv';
        $includeNoShows = $validated['include_no_shows'] ?? false;
        $startTime = $validated['start_time'] ?? null;
        $endTime = $validated['end_time'] ?? null;

        $query = Ticket::where('event_id', $eventId)
            ->where(function ($q) use ($includeNoShows) {
                $q->where('status', 'checked_in');
                if ($includeNoShows) {
                    $q->orWhere('status', 'valid');
                }
            });

        if ($startTime) {
            $query->where('checked_in_at', '>=', $startTime);
        }
        if ($endTime) {
            $query->where('checked_in_at', '<=', $endTime);
        }

        $tickets = $query->orderBy('checked_in_at', 'desc')->get();

        $data = $tickets->map(fn ($t) => [
            'ticket_id' => $t->id,
            'ticket_reference' => $t->ticket_id,
            'attendee_name' => $t->attendee_name,
            'attendee_email' => $t->attendee_email,
            'status' => $t->status,
            'checked_in_at' => $t->checked_in_at?->toDateTimeString(),
            'checked_in_by' => $t->checked_in_by,
        ]);

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => [
                    'event_id' => $eventId,
                    'format' => 'json',
                    'exported_at' => now()->toDateTimeString(),
                    'total_records' => $data->count(),
                    'records' => $data,
                ],
            ]);
        }

        // CSV export
        $filename = 'checkins_' . $eventId . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($data) {
            $output = fopen('php://output', 'w');

            // Header row
            fputcsv($output, ['Ticket ID', 'Ticket Reference', 'Attendee Name', 'Attendee Email', 'Status', 'Checked In At', 'Checked In By']);

            foreach ($data as $row) {
                fputcsv($output, [
                    $row['ticket_id'],
                    $row['ticket_reference'],
                    $row['attendee_name'],
                    $row['attendee_email'],
                    $row['status'],
                    $row['checked_in_at'] ?? '',
                    $row['checked_in_by'] ?? '',
                ]);
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
