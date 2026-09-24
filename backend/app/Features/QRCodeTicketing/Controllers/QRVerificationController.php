<?php

namespace App\Features\QRCodeTicketing\Controllers;

use App\Features\Checkout\Models\Ticket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Features\CheckIn\Policies\CheckInPolicy;

class QRVerificationController extends Controller
{
    public function __construct(private readonly CheckInPolicy $checkInPolicy)
    {
    }

    /**
     * Decrypt and verify an incoming ticket QR code payload.
     * Requires the caller to be authenticated (auth:sanctum) and to be a
     * venue staff member (admin, organizer, or venue_staff) who may access
     * the event in the
     * decrypted QR payload.
     *
     * POST /api/venue/check-in/qr
     *
     * @throws 401  Unauthenticated
     * @throws 403  Not a venue staff member OR event in QR payload is not owned by this user
     * @throws 422  Malformed payload
     * @throws 400  Decryption failed (corrupt or forged payload)
     */
    public function verify(Request $request)
    {
        // 1. Authenticate — auth:sanctum middleware already ran on the route
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // 2. Authorize venue staff role
        if (!$this->checkInPolicy->isVenueStaff($user)) {
            abort(403, 'Only venue staff may verify QR codes.');
        }

        $validated = $request->validate([
            'encrypted_payload' => ['required', 'string'],
        ]);

        try {
            // 3. Decrypt the payload using dedicated encryption service
            $payload = \App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::decrypt($validated['encrypted_payload']);

            if (!$payload || !isset($payload['ticket_id']) || !isset($payload['event_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR payload format.',
                ], 422);
            }

            // 4. Verify HMAC signature to protect against tampering
            $signature = $payload['signature'] ?? '';
            unset($payload['signature']);
            if (!\App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::verifySignature($payload, $signature)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code signature mismatch. Counterfeit attempt suspected.',
                ], 403);
            }

            // 5. Verify nonce to prevent replay attacks
            $ticket = \App\Features\Checkout\Models\Ticket::find($payload['ticket_id']);
            if ($ticket && isset($payload['nonce']) && $ticket->qr_nonce && hash_equals($ticket->qr_nonce, $payload['nonce'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code has already been used.',
                ], 403);
            }

            $eventId = $payload['event_id'];
            $eventModel = \App\Models\Event::find($eventId);
            if (!$eventModel) {
                abort(404, 'Event not found.');
            }
            if (!$this->checkInPolicy->canAccessEvent($user, $eventModel)) {
                abort(403, 'You do not have permission to verify tickets for this event.');
            }

            // At this point the ticket is decrypted, verified authentic,
            // and the venue staff is authorized to handle it.
            $nonce = $payload['nonce'] ?? null;
            \App\Models\AuditLog::create([
                'action' => 'qr_verified',
                'target_type' => \App\Features\Checkout\Models\Ticket::class,
                'target_id' => $payload['ticket_id'],
                'user_id' => $user->id,
                'metadata' => [
                    'event_id' => $payload['event_id'],
                    'nonce' => $nonce,
                    'method' => 'venue_staff',
                ],
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket decrypted and verified successfully.',
                'data' => [
                    'ticket_id' => $payload['ticket_id'],
                    'event_id' => $payload['event_id'],
                    'generated_at' => $payload['generated_at'] ?? null,
                    'verified_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('QR Verification General Failure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal validation failure.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Verify a QR code payload for the ticket owner (user-facing).
     *
     * POST /api/tickets/verify-qr
     */
    public function verifyForUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $validated = $request->validate([
            'qr_code_data' => ['required', 'string'],
        ]);

        try {
            $payload = \App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::decrypt($validated['qr_code_data']);

            if (!$payload || !isset($payload['ticket_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR payload format.',
                ], 422);
            }

            // Verify HMAC signature
            $signature = $payload['signature'] ?? '';
            unset($payload['signature']);
            if (!\App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::verifySignature($payload, $signature)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR Code signature mismatch.',
                ], 403);
            }

            $ticket = Ticket::with(['event', 'ticketTier'])->find($payload['ticket_id']);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found.',
                ], 404);
            }

            // Defense-in-depth: verify event_id in payload matches ticket's event
            if (isset($payload['event_id']) && (string) $payload['event_id'] !== (string) $ticket->event_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code event mismatch.',
                ], 403);
            }

            // Verify nonce to prevent replay attacks
            if (isset($payload['nonce']) && $ticket->qr_nonce && hash_equals($ticket->qr_nonce, $payload['nonce'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code has already been used.',
                ], 403);
            }

            // Ownership check — only the ticket owner can verify their own QR
            if ((string) $ticket->user_id !== (string) $user->id && !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this ticket.',
                ], 403);
            }

            // Check expiry
            if ($ticket->qr_code_expires_at && now()->gt($ticket->qr_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code expired'
                ], 410);
            }

            // Check if already checked in
            if ($ticket->checked_in || $ticket->status === 'checked_in') {
                $checkedInAt = $ticket->checked_in_at;
                return response()->json([
                    'success' => true,
                    'message' => 'Already checked in at ' . ($checkedInAt ? $checkedInAt->format('H:i') : 'unknown'),
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_reference' => $ticket->ticket_id,
                        'status' => 'checked_in',
                        'checked_in' => true,
                        'checked_in_at' => $checkedInAt?->toDateTimeString(),
                    ],
                ]);
            }

            // Check ticket status
            if ($ticket->status === 'void') {
                return response()->json([
                    'success' => false,
                    'message' => 'This ticket is invalid',
                ], 403);
            }

            // Check for fraud flags
            $hasFraud = \App\Features\Fraud\Models\FraudEvent::where('ticket_id', $ticket->id)
                ->whereIn('status', ['flagged', 'auto_blocked'])
                ->exists();

            if ($hasFraud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket blocked due to fraud investigation.',
                ], 403);
            }

            $nonce = $payload['nonce'] ?? null;
            \App\Models\AuditLog::create([
                'action' => 'qr_verified',
                'target_type' => Ticket::class,
                'target_id' => $ticket->id,
                'user_id' => $user->id,
                'metadata' => [
                    'event_id' => $ticket->event_id,
                    'nonce' => $nonce,
                    'method' => 'user',
                ],
                'ip_address' => request()->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'QR code verified successfully.',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_reference' => $ticket->ticket_id,
                    'status' => $ticket->status,
                    'event_name' => $ticket->event->title ?? null,
                    'event_start' => $ticket->event->start_datetime?->toDateTimeString(),
                    'tier_name' => $ticket->ticketTier->name ?? null,
                    'checked_in' => (bool) $ticket->checked_in,
                    'checked_in_at' => $ticket->checked_in_at?->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('QR Verification General Failure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal validation failure.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
