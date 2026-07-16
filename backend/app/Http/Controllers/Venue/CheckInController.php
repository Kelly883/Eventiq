<?php

namespace App\Http\Controllers\Venue;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\CheckIn;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class CheckInController extends Controller
{
    /**
     * Store a client-submitted or synchronized check-in scan.
     * Enforces client_mutation_id idempotency and broadcasts to event.{id}.stats channels.
     * 
     * POST /api/venue/check-in
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ticket_code' => ['required', 'string'],
            'event_id' => ['required'],
            'scanned_at' => ['required', 'string'],
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

        // 2. Attempt Decryption and Cryptographic Verification if encrypted
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
                Log::warning("Decryption failed for scanned ticket. Proceeding to search as raw: " . $e->getMessage());
            } catch (\Exception $e) {
                Log::error("QR validation general exception: " . $e->getMessage());
            }
        }

        // 3. Look up ticket
        if ($decryptedSuccess && $ticketId) {
            $ticket = Ticket::find($ticketId);
        } else {
            // Search by raw qr_code code field or primary key ID
            $ticket = Ticket::where('qr_code', $ticketCode)
                ->orWhere('id', $ticketCode)
                ->first();
        }

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found or invalid.',
            ], 404);
        }

        // 4. Double check ticket hasn't been checked in yet by another client
        if ($ticket->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket already checked in.',
                'checked_in_at' => $ticket->checked_in_at,
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
        $checkIn->checked_in_at = $scannedAt ?: now();
        $checkIn->client_mutation_id = $clientMutationId;
        $checkIn->save();

        // 7. Real-time Broadcasting grouped under specific event.{id}.stats channels
        try {
            $pusherKey = env('PUSHER_APP_KEY', 'eventiq_pusher_key');
            $pusherSecret = env('PUSHER_APP_SECRET');
            $pusherAppId = env('PUSHER_APP_ID');
            $pusherCluster = env('PUSHER_APP_CLUSTER', 'mt1');
            $pusherHost = env('PUSHER_HOST');
            $pusherPort = env('PUSHER_PORT', 443);
            $pusherScheme = env('PUSHER_SCHEME', 'https');

            if ($pusherKey && $pusherSecret && $pusherAppId) {
                $options = [
                    'cluster' => $pusherCluster,
                    'useTLS' => true,
                ];
                if ($pusherHost) {
                    $options['host'] = $pusherHost;
                    $options['port'] = $pusherPort;
                    $options['scheme'] = $pusherScheme;
                }

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
