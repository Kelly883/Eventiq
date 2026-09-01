<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\CheckIn;
use App\Features\CheckIn\Policies\CheckInPolicy;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

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
     * Store a client-submitted or synchronized check-in scan.
     * Enforces client_mutation_id idempotency and broadcasts to event.{id}.stats channels.
     *
     * POST /api/venue/check-in
     */
    public function store(Request $request)
    {
        $this->authorizeVenueStaff($request->user());

        $validated = $request->validate([
            'ticket_code' => ['required', 'string'],
            'event_id' => ['required'],
            'scanned_at' => ['required', 'date'],
            'client_mutation_id' => ['required', 'string'],
        ]);

        $ticketCode = $validated['ticket_code'];
        $eventId = $validated['event_id'];
        $scannedAt = $validated['scanned_at'];
        $clientMutationId = $validated['client_mutation_id'];

        // 1. Actively verify and store client-side UUID to prevent multi-scan duplicate credits
        $existingCheckIn = CheckIn::where('client_mutation_id', $clientMutationId)->first();
        if ($existingCheckIn) {
            Log::info("Idempotent check-in detected for client_mutation_id: {$clientMutationId}");
            return response()->json([
                'success' => true,
                'message' => 'Check-in already processed (idempotent duplicate).',
                'id' => $existingCheckIn->id,
                'ticket_id' => $existingCheckIn->ticket_id,
                'is_duplicate' => true,
            ]);
        }

        $ticketId = null;
        $decryptedSuccess = false;

        // 2. Cryptographic verification is mandatory: only signed QR payloads are accepted.
        if (str_starts_with($ticketCode, 'ey') || strlen($ticketCode) > 60) {
            try {
                $decryptedRaw = Crypt::decryptString($ticketCode);
                $payload = json_decode($decryptedRaw, true);

                if ($payload && isset($payload['ticket_id'])) {
                    $ticketId = $payload['ticket_id'];
                    $eventId = $payload['event_id'] ?? $eventId;

                    // Verify HMAC signature
                    $expectedSignature = hash_hmac('sha256', "{$eventId}-{$ticketId}", config('app.key'));
                    if (hash_equals($expectedSignature, $payload['signature'] ?? '')) {
                        $decryptedSuccess = true;
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'QR Code signature mismatch. Counterfeit attempt suspected.',
                        ], 403);
                    }
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                Log::warning("Decryption failed for scanned ticket. Rejecting scan: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("QR validation general exception: " . $e->getMessage());
            }
        }

        if (!$decryptedSuccess || !$ticketId) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ticket code. A valid signed QR code is required.',
            ], 422);
        }

        // 3. Look up ticket by verified payload only — no raw-code or ID enumeration fallback.
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found or invalid.',
            ], 404);
        }

        $user = $request->user();
        $event = \App\Models\Event::find($ticket->event_id);
        if (!$event || !$this->checkInPolicy->canAccessEvent($user, $event)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to check in tickets for this event.',
            ], 403);
        }

        // 4. Double check ticket hasn't been checked in yet by another client
        if ($ticket->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket already checked in.',
                'checked_in_at' => $ticket->checked_in_at,
            ], 422);
        }

        // 4a. Application-level duplicate-scan guard: prevent multiple
        // successful check-in records for the same ticket at the DB level.
        $alreadyCheckedIn = CheckIn::where('ticket_id', $ticket->id)
            ->where('status', 'checked_in')
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket already checked in.',
                'ticket_id' => $ticket->id,
                'is_duplicate' => true,
            ], 422);
        }

        // 5. Update Ticket check-in state
        $ticket->checked_in = true;
        $ticket->checked_in_at = $scannedAt ?: now();
        $ticket->save();

        // 6. Record persistent CheckIn event
        $checkIn = new CheckIn();
        $checkIn->ticket_id = $ticket->id;
        $checkIn->user_id = $ticket->user_id ?? null;
        $checkIn->event_id = $ticket->event_id ?? null;
        $checkIn->scanned_by = auth()->id();
        $checkIn->status = 'checked_in';
        $checkIn->checked_in_at = $scannedAt ?: now();
        $checkIn->client_mutation_id = $clientMutationId;
        $checkIn->save();

        // 7. Real-time Broadcasting grouped under specific event.{id}.stats channels
        try {
            $pusherKey = config('broadcasting.connections.pusher.key');
            $pusherSecret = config('broadcasting.connections.pusher.secret');
            $pusherAppId = config('broadcasting.connections.pusher.app_id');
            $pusherCluster = config('broadcasting.connections.pusher.options.cluster', 'mt1');

            if ($pusherKey && $pusherSecret && $pusherAppId) {
                $options = [
                    'cluster' => $pusherCluster,
                    'useTLS' => true,
                ];

                $pusher = new \Pusher\Pusher(
                    $pusherKey,
                    $pusherSecret,
                    $pusherAppId,
                    $options
                );

                // Fetch current statistics for the specific event
                $totalTickets = Ticket::where('event_id', $eventId)->count();
                $processedTickets = Ticket::where('event_id', $eventId)->where('checked_in', true)->count();

                $statsPayload = [
                    'stats' => [
                        'total' => $totalTickets,
                        'processed' => $processedTickets,
                        'last_scanned_at' => now()->toIso8601String(),
                    ],
                    'event_id' => $eventId,
                ];

                // Publish update to event.{id}.stats channel
                $channelName = "event.{$eventId}.stats";
                $pusher->trigger($channelName, 'CheckInProcessed', $statsPayload);
                Log::info("Broadcasted check-in stats successfully to {$channelName}");
            }
        } catch (\Exception $broadcastException) {
            Log::warning("Pusher stats broadcast skipped or failed: " . $broadcastException->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in processed successfully.',
            'id' => $checkIn->id,
            'ticket_id' => $ticket->id,
            'checked_in_at' => $checkIn->checked_in_at,
        ]);
    }
}
